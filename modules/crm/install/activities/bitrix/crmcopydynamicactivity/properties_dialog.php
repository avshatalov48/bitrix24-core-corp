<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcopydynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?=
			$dialog->getFieldTypeObject($field)->renderControl(
				[
					'Form' => $dialog->getFormName(),
					'Field' => $field['FieldName']
				],
				$dialog->getCurrentValue($field['FieldName']),
				true,
				0
			)
			?>
		</td>
	</tr>
<?php endforeach;?>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmCopyDynamicActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>'
		});
		script.init();
	});
</script>
