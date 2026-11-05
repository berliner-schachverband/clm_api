<?php

namespace Bsv\Component\Clmapi\Administrator\View\Keys;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if ($this->items === false) {
            throw new GenericDataException(implode("\n", $model->getErrors()), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_CLMAPI_KEYS_TITLE'), 'key');

        $toolbar->addNew('key.add');
        $toolbar->standardButton('revoke', 'COM_CLMAPI_REVOKE', 'keys.revoke')
            ->icon('icon-ban')
            ->listCheck(true);
        $toolbar->standardButton('restore', 'COM_CLMAPI_RESTORE', 'keys.restore')
            ->icon('icon-check')
            ->listCheck(true);
        $toolbar->delete('keys.delete', 'JTOOLBAR_DELETE')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
    }
}
