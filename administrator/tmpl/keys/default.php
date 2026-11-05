<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Bsv\Component\Clmapi\Administrator\View\Keys\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$nowTs     = Factory::getDate()->toUnix();

$fmt = static function (?string $date): string {
    return $date ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC5')) : '–';
};
?>
<form action="<?php echo Route::_('index.php?option=com_clmapi&view=keys'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="keyList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_CLMAPI_KEYS_TITLE'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CLMAPI_COL_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                                <th scope="col"><?php echo Text::_('COM_CLMAPI_COL_PREFIX'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_CLMAPI_COL_CONTACT'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_CLMAPI_COL_STATUS'); ?></th>
                                <th scope="col" class="text-end"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CLMAPI_COL_RATE_LIMIT', 'a.rate_limit', $listDirn, $listOrder); ?></th>
                                <th scope="col" class="text-end"><?php echo Text::_('COM_CLMAPI_COL_HITS_24H'); ?></th>
                                <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CLMAPI_COL_LAST_USED', 'a.last_used_at', $listDirn, $listOrder); ?></th>
                                <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CLMAPI_COL_EXPIRES', 'a.expires_at', $listDirn, $listOrder); ?></th>
                                <th scope="col" class="w-1"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                if ($item->revoked_at) {
                                    $badge = ['bg-danger', 'COM_CLMAPI_STATUS_REVOKED'];
                                } elseif ($item->expires_at && strtotime($item->expires_at . ' UTC') < $nowTs) {
                                    $badge = ['bg-warning text-dark', 'COM_CLMAPI_STATUS_EXPIRED'];
                                } else {
                                    $badge = ['bg-success', 'COM_CLMAPI_STATUS_ACTIVE'];
                                }
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?></td>
                                    <th scope="row">
                                        <a href="<?php echo Route::_('index.php?option=com_clmapi&task=key.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->name); ?>
                                        </a>
                                    </th>
                                    <td><code><?php echo $this->escape($item->key_prefix); ?>…</code></td>
                                    <td><?php echo $this->escape((string) $item->contact); ?></td>
                                    <td><span class="badge <?php echo $badge[0]; ?>"><?php echo Text::_($badge[1]); ?></span></td>
                                    <td class="text-end"><?php echo (int) $item->rate_limit; ?>/min</td>
                                    <td class="text-end"><?php echo number_format((int) $item->hits_24h, 0, ',', '.'); ?></td>
                                    <td><?php echo $fmt($item->last_used_at); ?></td>
                                    <td><?php echo $fmt($item->expires_at); ?></td>
                                    <td><?php echo (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
