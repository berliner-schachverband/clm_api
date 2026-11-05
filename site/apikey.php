<?php
/**
 * apikey.php – API-Key-Prüfung für die JSON-Skripte des Ergebnisdienstes.
 *
 * Einbindung ganz oben in jedem Skript, vor jeder Ausgabe:
 *
 *     require_once __DIR__ . '/apikey.php';
 *     bsv_require_api_key();
 *
 * Der Key wird im Header "X-API-Key" erwartet (bewusst nicht in der URL).
 * Bei fehlendem, ungültigem, gesperrtem oder abgelaufenem Key sowie bei
 * überschrittenem Limit wird eine JSON-Fehlerantwort gesendet und das
 * Skript beendet. Bei Datenbankproblemen wird ebenfalls abgewiesen (503).
 *
 * Die Datenbankverbindung stammt aus der configuration.php von Joomla;
 * das Skript funktioniert innerhalb und außerhalb von Joomla.
 *
 * Optionen: bsv_require_api_key(['config' => '/pfad/zur/configuration.php']);
 */

if (!function_exists('bsv_require_api_key')) {

    function bsv_api_fail(int $status, string $message, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    function bsv_api_find_config(): string
    {
        if (defined('JPATH_CONFIGURATION') && is_file(JPATH_CONFIGURATION . '/configuration.php')) {
            return JPATH_CONFIGURATION . '/configuration.php';
        }

        $dir = __DIR__;
        for ($i = 0; $i < 8; $i++) {
            if (is_file($dir . '/configuration.php') && is_dir($dir . '/administrator')) {
                return $dir . '/configuration.php';
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        throw new RuntimeException('Joomla configuration.php nicht gefunden');
    }

    /** @return array{0: PDO, 1: string} */
    function bsv_api_db(string $configFile): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        if (!class_exists('JConfig', false)) {
            require_once $configFile;
        }
        $c = new JConfig();

        $host = (string) $c->host;
        $port = null;
        $socket = null;
        if (strpos($host, ':') !== false) {
            [$host, $rest] = explode(':', $host, 2);
            if ($rest !== '' && $rest[0] === '/') {
                $socket = $rest;
            } elseif (ctype_digit($rest)) {
                $port = (int) $rest;
            }
        }

        $dsn = $socket !== null
            ? 'mysql:unix_socket=' . $socket . ';dbname=' . $c->db . ';charset=utf8mb4'
            : 'mysql:host=' . $host . ($port ? ';port=' . $port : '') . ';dbname=' . $c->db . ';charset=utf8mb4';

        $pdo = new PDO($dsn, (string) $c->user, (string) $c->password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $prefix = preg_replace('/[^A-Za-z0-9_]/', '', (string) $c->dbprefix);

        return $cache = [$pdo, $prefix];
    }

    /**
     * Prüft den API-Key und das Rate-Limit. Gibt bei Erfolg die Key-Zeile zurück
     * (id, name, rate_limit); sonst Fehlerantwort und exit.
     */
    function bsv_require_api_key(array $options = []): array
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['REDIRECT_HTTP_X_API_KEY'] ?? '';
        if ($key === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'X-API-Key') === 0) {
                    $key = $value;
                }
            }
        }
        $key = trim((string) $key);

        if ($key === '') {
            bsv_api_fail(401, 'API-Key fehlt (Header X-API-Key)');
        }
        if (strlen($key) > 128) {
            bsv_api_fail(403, 'API-Key ungültig');
        }

        try {
            [$pdo, $p] = bsv_api_db($options['config'] ?? bsv_api_find_config());
            $keys  = '`' . $p . 'clmapi_keys`';
            $usage = '`' . $p . 'clmapi_usage`';

            // Joomla speichert Zeiten in UTC
            $st = $pdo->prepare(
                "SELECT id, name, rate_limit FROM $keys
                 WHERE key_hash = ? AND revoked_at IS NULL
                   AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())"
            );
            $st->execute([hash('sha256', $key)]);
            $row = $st->fetch();

            if (!$row) {
                bsv_api_fail(403, 'API-Key ungültig, gesperrt oder abgelaufen');
            }

            $id   = (int) $row['id'];
            $slot = intdiv(time(), 60);

            $pdo->prepare(
                "INSERT INTO $usage (api_key_id, minute_slot, hits) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE hits = hits + 1"
            )->execute([$id, $slot]);

            $st = $pdo->prepare("SELECT hits FROM $usage WHERE api_key_id = ? AND minute_slot = ?");
            $st->execute([$id, $slot]);
            $hits  = (int) $st->fetchColumn();
            $limit = (int) $row['rate_limit'];

            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . max(0, $limit - $hits));

            if ($hits > $limit) {
                bsv_api_fail(429, 'Rate-Limit überschritten', ['Retry-After' => (string) (60 - (time() % 60))]);
            }

            // "zuletzt benutzt" höchstens einmal pro Minute schreiben
            if ($hits === 1) {
                $pdo->prepare("UPDATE $keys SET last_used_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$id]);
            }

            // Alte Zähler gelegentlich aufräumen (kein Cronjob nötig)
            if (random_int(1, 200) === 1) {
                $pdo->prepare("DELETE FROM $usage WHERE minute_slot < ?")->execute([$slot - 2880]);
            }

            return $row;
        } catch (Throwable $e) {
            error_log('[apikey] ' . $e->getMessage());
            bsv_api_fail(503, 'API-Key-Prüfung derzeit nicht möglich');
        }
    }
}
