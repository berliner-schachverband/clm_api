<?php

namespace Bsv\Component\Clmapi\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

class KeyTable extends Table
{
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__clm_api_keys', 'id', $db, $dispatcher);
    }

    public function check()
    {
        $this->name = trim((string) $this->name);

        if ($this->name === '') {
            $this->setError(Text::_('COM_CLMAPI_ERROR_NAME_REQUIRED'));

            return false;
        }

        if ((int) $this->rate_limit < 1) {
            $this->rate_limit = 60;
        }

        foreach (['expires_at', 'revoked_at', 'last_used_at'] as $field) {
            if (empty($this->$field)) {
                $this->$field = null;
            }
        }

        return true;
    }

    /** Leere Felder (z. B. "Gültig bis") müssen beim Speichern wirklich NULL werden. */
    public function store($updateNulls = true)
    {
        return parent::store($updateNulls);
    }
}
