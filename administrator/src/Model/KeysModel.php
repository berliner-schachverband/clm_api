<?php

namespace Bsv\Component\Clmapi\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class KeysModel extends ListModel
{
    public function __construct($config = [], $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'rate_limit', 'a.rate_limit',
                'created_at', 'a.created_at',
                'last_used_at', 'a.last_used_at',
                'expires_at', 'a.expires_at',
            ];
        }

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'a.created_at', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $since = intdiv(time(), 60) - 1440;

        $query->select($db->quoteName('a') . '.*')
            ->select(
                '(SELECT COALESCE(SUM(u.`hits`), 0) FROM ' . $db->quoteName('#__clm_api_usage', 'u')
                . ' WHERE u.`api_key_id` = a.`id` AND u.`minute_slot` >= ' . $since . ') AS ' . $db->quoteName('hits_24h')
            )
            ->from($db->quoteName('#__clm_api_keys', 'a'));

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where('(a.`name` LIKE :s1 OR a.`contact` LIKE :s2 OR a.`key_prefix` LIKE :s3)')
                ->bind(':s1', $like)
                ->bind(':s2', $like)
                ->bind(':s3', $like);
        }

        $status = (string) $this->getState('filter.status');
        $now    = Factory::getDate()->toSql();

        if ($status === 'revoked') {
            $query->where('a.`revoked_at` IS NOT NULL');
        } elseif ($status === 'expired') {
            $query->where('a.`revoked_at` IS NULL')
                ->where('a.`expires_at` IS NOT NULL')
                ->where('a.`expires_at` <= :now')
                ->bind(':now', $now);
        } elseif ($status === 'active') {
            $query->where('a.`revoked_at` IS NULL')
                ->where('(a.`expires_at` IS NULL OR a.`expires_at` > :now)')
                ->bind(':now', $now);
        }

        $ordering  = $db->escape($this->getState('list.ordering', 'a.created_at'));
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order($ordering . ' ' . $direction);

        return $query;
    }
}
