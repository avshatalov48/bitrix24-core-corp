<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcopydynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $field):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?=htmlspecialcharsbx($field['Name'])?>:
		</span>
		<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
	</div>
<?php endforeach;?>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmCopyDynamicActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>'
		});
		script.init();
	})
</script>