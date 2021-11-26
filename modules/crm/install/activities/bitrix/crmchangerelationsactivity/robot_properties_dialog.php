<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangerelationsactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>
<?php foreach ($dialog->getMap() as $field): ?>
	<?php if (array_key_exists('Name', $field)): ?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
				<?=htmlspecialcharsbx($field['Name'])?>:
			</span>
			<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
		</div>
	<?php endif; ?>
<?php endforeach;?>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmChangeRelationsActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
		});
		script.init();
	})
</script>
