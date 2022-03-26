<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangedealcategoryactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):
	?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<? $filedType = $dialog->getFieldTypeObject($field);

			echo $filedType->renderControl(array(
				'Form' => $dialog->getFormName(),
				'Field' => $field['FieldName']
			), $dialog->getCurrentValue($field['FieldName']), true, 0);
			?>
		</td>
	</tr>
<?endforeach;?>

<tr>
	<td align="right" width="40%"></td>
	<td width="60%">
		<?=GetMessage('CRM_CDCA_PD_INFO_1')?>
	</td>
</tr>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmChangeDealCategoryActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>'
		});
		script.init();
	});
</script>
