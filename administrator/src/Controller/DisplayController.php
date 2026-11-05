<?php

namespace Bsv\Component\Clmapi\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class DisplayController extends BaseController
{
    protected $default_view = 'keys';

    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', 'keys');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Direktaufruf des Bearbeitungsformulars ohne vorheriges "edit" verhindern
        if ($view === 'key' && $layout === 'edit' && !$this->checkEditId('com_clmapi.edit.key', $id)) {
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_clmapi&view=keys', false));

            return false;
        }

        return parent::display();
    }
}
