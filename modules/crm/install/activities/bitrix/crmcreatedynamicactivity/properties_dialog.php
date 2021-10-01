<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcreatedynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$chosenEntityTypeId = (int)$dialog->getCurrentValue('dynamic_type_id', 0);
$chosenEntityValues = $dialog->getCurrentValue('dynamic_entities_fields');

$dynamicTypeIdField = $dialog->getMap()['DynamicTypeId'];
$dynamicEntitiesFields = $dialog->getMap()['DynamicEntitiesFields']['Map'];
?>
<tr>
	<td align="right" width="40%"><?=htmlspecialcharsbx($dynamicTypeIdField['Name'])?>:</td>
	<td width="60%">
		<?=
		$dialog->getFieldTypeObject($dynamicTypeIdField)->renderControl(
			[
				'Form' => $dialog->getFormName(),
				'Field' => $dynamicTypeIdField['FieldName']
			],
			$dialog->getCurrentValue($dynamicTypeIdField['FieldName']),
			true,
			0
		)
		?>
	</td>
</tr>

<tr>
	<td colspan="2">
		<?php foreach ($dynamicEntitiesFields as $entityTypeId => $fields): ?>
			<table
				id="ccda-fields-map-<?= $entityTypeId ?>"
				<?= $entityTypeId !== $chosenEntityTypeId ? 'hidden' : ''?>
				border="0"
				cellpadding="2"
				cellspacing="2"
			>
				<?php foreach ($fields as $fieldId => $field): ?>
					<tr>
						<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
						<td width="60%">
							<?=
							$dialog->getFieldTypeObject($field)->renderControl(
								[
									'Form' => $dialog->getFormName(),
									'Field' => $field['FieldName']
								],
								$dialog->getCurrentValue($field['FieldName'], $chosenEntityValues[$fieldId]),
								true,
								0
							)
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>
	</td>
</tr>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmCreateDynamicActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			fieldsContainerIdPrefix: 'ccda-fields-map-',
		});
		script.init();
	});
</script>
