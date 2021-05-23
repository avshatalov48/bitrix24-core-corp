<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmgetrequisitesinfoactivity/script.js'));

$map = $dialog->getMap();
if(count($map['CountryId']['Options']) <= 1)
{
	unset($map['CountryId']);
}

?>

<?php foreach ($map as $field):?>
	<?php if(array_key_exists('Name', $field)): ?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($field['Name'])?>: </span>
			<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
		</div>
	<?php endif ?>
<?endforeach;?>

<script>
	BX.ready(function()
	{
		BX.Crm.Activity.CrmGetRequisitesInfoActivity.init({
			selectCountryNodeId: "id_" + "<?=$map['CountryId']['FieldName']?>",
			selectPresetNodeId: "id_" + "<?=$map['RequisitePresetId']['FieldName']?>",
			countriesOfPresets: <?=CUtil::PhpToJSObject($dialog->getRuntimeData()['PresetsInfo'])?>
		});
	});
</script>
