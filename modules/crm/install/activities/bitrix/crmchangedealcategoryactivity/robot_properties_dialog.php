<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangedealcategoryactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $field):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($field['Name'])?>: </span>
		<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
	</div>
<?php endforeach;?>

<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
	<?=GetMessage('CRM_CDCA_RPD_INFO')?>
</div>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmChangeDealCategoryActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>'
		});
		script.init();
	});
</script>
