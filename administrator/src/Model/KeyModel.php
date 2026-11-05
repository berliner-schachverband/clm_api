<?php

namespace Bsv\Component\Clmapi\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

class KeyModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_clmapi.key', 'key', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_clmapi.edit.key.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $db    = $this->getDatabase();
            $id    = (int) $item->id;
            $since = intdiv(time(), 60) - 1440;

            $item->hits_24h = (int) $db->setQuery(
                $db->getQuery(true)
                    ->select('COALESCE(SUM(' . $db->quoteName('hits') . '), 0)')
                    ->from($db->quoteName('#__clm_api_usage'))
                    ->where($db->quoteName('api_key_id') . ' = :id')
                    ->where($db->quoteName('minute_slot') . ' >= :since')
                    ->bind(':id', $id, ParameterType::INTEGER)
                    ->bind(':since', $since, ParameterType::INTEGER)
            )->loadResult();
        }

        return $item;
    }

    /**
     * Beim Anlegen wird der Key erzeugt; nur der Hash wird gespeichert.
     * Der Klartext erscheint genau einmal als Systemmeldung.
     */
    public function save($data)
    {
        $plain = null;

        if (empty($data['id'])) {
            $plain                = 'bsv_' . bin2hex(random_bytes(24));
            $data['key_prefix']   = substr($plain, 0, 8);
            $data['key_hash']     = hash('sha256', $plain);
            $data['created_at']   = Factory::getDate()->toSql();
        }

        if (!parent::save($data)) {
            return false;
        }

        if ($plain !== null) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_CLMAPI_KEY_CREATED', '<code>' . htmlspecialchars($plain, ENT_QUOTES, 'UTF-8') . '</code>'),
                'warning'
            );
        }

        return true;
    }

    public function setRevoked(array $pks, bool $revoked): bool
    {
        $pks = array_filter(array_map('intval', $pks));

        if (!$pks) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__clm_api_keys'))
            ->whereIn($db->quoteName('id'), $pks);

        if ($revoked) {
            $now = Factory::getDate()->toSql();
            $query->set($db->quoteName('revoked_at') . ' = :now')->bind(':now', $now);
        } else {
            $query->set($db->quoteName('revoked_at') . ' = NULL');
        }

        $db->setQuery($query)->execute();

        return true;
    }

    public function delete(&$pks)
    {
        $ids = array_filter(array_map('intval', (array) $pks));

        if (!parent::delete($pks)) {
            return false;
        }

        if ($ids) {
            $db = $this->getDatabase();
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__clm_api_usage'))
                    ->whereIn($db->quoteName('api_key_id'), $ids)
            )->execute();
        }

        return true;
    }
}
