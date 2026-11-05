<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Bsv\Component\Clmapi\Administrator\View\Key\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');

$id    = (int) ($this->item->id ?? 0);
$isNew = $id === 0;

$fmt = static function (?string $date): string {
    return $date ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC5')) : '–';
};
?>
<form action="<?php echo Route::_('index.php?option=com_clmapi&layout=edit&id=' . $id); ?>"
      method="post" name="adminForm" id="key-form" class="form-validate">

    <div class="row">
        <div class="col-lg-8">
            <?php echo $this->form->renderField('id'); ?>
            <?php echo $this->form->renderField('name'); ?>
            <?php echo $this->form->renderField('contact'); ?>
            <?php echo $this->form->renderField('rate_limit'); ?>
            <?php echo $this->form->renderField('expires_at'); ?>
            <?php echo $this->form->renderField('note'); ?>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5"><?php echo Text::_('COM_CLMAPI_KEY_INFO'); ?></h2>
                    <?php if ($isNew) : ?>
                        <p><?php echo Text::_('COM_CLMAPI_KEY_NEW_HINT'); ?></p>
                    <?php else : ?>
                        <dl class="row mb-0">
                            <dt class="col-sm-5"><?php echo Text::_('COM_CLMAPI_COL_PREFIX'); ?></dt>
                            <dd class="col-sm-7"><code><?php echo $this->escape($this->item->key_prefix); ?>…</code></dd>
                            <dt class="col-sm-5"><?php echo Text::_('COM_CLMAPI_COL_CREATED'); ?></dt>
                            <dd class="col-sm-7"><?php echo $fmt($this->item->created_at); ?></dd>
                            <dt class="col-sm-5"><?php echo Text::_('COM_CLMAPI_COL_LAST_USED'); ?></dt>
                            <dd class="col-sm-7"><?php echo $fmt($this->item->last_used_at); ?></dd>
                            <dt class="col-sm-5"><?php echo Text::_('COM_CLMAPI_COL_HITS_24H'); ?></dt>
                            <dd class="col-sm-7"><?php echo (int) ($this->item->hits_24h ?? 0); ?></dd>
                            <dt class="col-sm-5"><?php echo Text::_('COM_CLMAPI_COL_STATUS'); ?></dt>
                            <dd class="col-sm-7">
                                <?php echo $this->item->revoked_at
                                    ? Text::_('COM_CLMAPI_STATUS_REVOKED') . ' (' . $fmt($this->item->revoked_at) . ')'
                                    : Text::_('COM_CLMAPI_STATUS_ACTIVE'); ?>
                            </dd>
                        </dl>
                        <hr>
                        <p class="small text-muted mb-0"><?php echo Text::_('COM_CLMAPI_KEY_IMMUTABLE_NOTE'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
