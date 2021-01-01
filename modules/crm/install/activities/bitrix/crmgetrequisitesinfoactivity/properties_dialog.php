<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmgetrequisitesinfoactivity/script.js'));

$map = $dialog->getMap();
if(count($map['CountryId']['Options']) <= 1)
{
	unset($map['CountryId']);
}

foreach ($map as $fieldId => $field):
	?>
	<?php if(array_key_exists('Name', $field)): ?>
		<tr>
			<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
			<td width="60%">
				<? $fieldObject = $dialog->getFieldTypeObject($field);

				if(is_null($fieldObject))
				{
					continue;
				}

				echo $fieldObject->renderControl(array(
					 'Form' => $dialog->getFormName(),
					 'Field' => $field['FieldName']
				), $dialog->getCurrentValue($field['FieldName']), true, 0);
				?>
			</td>
		</tr>
	<?php endif; ?>
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
