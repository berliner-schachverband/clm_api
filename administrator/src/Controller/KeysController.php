<?php

namespace Bsv\Component\Clmapi\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class KeysController extends AdminController
{
    protected $text_prefix = 'COM_CLMAPI';

    public function getModel($name = 'Key', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function revoke(): void
    {
        $this->changeRevoked(true);
    }

    public function restore(): void
    {
        $this->changeRevoked(false);
    }

    private function changeRevoked(bool $revoked): void
    {
        $this->checkToken();

        $ids = array_filter((array) $this->input->get('cid', [], 'array'));
        $ids = array_map('intval', $ids);

        if (!$ids) {
            $this->setMessage(Text::_('COM_CLMAPI_NO_SELECTION'), 'warning');
        } elseif ($this->getModel()->setRevoked($ids, $revoked)) {
            $this->setMessage(Text::sprintf($revoked ? 'COM_CLMAPI_N_REVOKED' : 'COM_CLMAPI_N_RESTORED', \count($ids)));
        } else {
            $this->setMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_clmapi&view=keys', false));
    }
}
