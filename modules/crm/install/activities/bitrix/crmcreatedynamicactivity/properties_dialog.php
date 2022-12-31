<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcreatedynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$chosenEntityTypeId = (int)$dialog->getCurrentValue('dynamic_type_id', 0);
$chosenEntityValues = $dialog->getCurrentValue('dynamic_entities_fields');

$typeIdField = $dialog->getMap()['DynamicTypeId'];
$entitiesFields = $dialog->getMap()['DynamicEntitiesFields']['Map'];

?>
<tr>
	<td align="right" width="40%"><?=htmlspecialcharsbx($typeIdField['Name'])?>:</td>
	<td width="60%">
		<?=
		$dialog->getFieldTypeObject($typeIdField)->renderControl(
			[
				'Form' => $dialog->getFormName(),
				'Field' => $typeIdField['FieldName']
			],
			$dialog->getCurrentValue($typeIdField['FieldName']),
			true,
			0
		)
		?>
	</td>
</tr>

<tr>
	<td colspan="2">
		<?php foreach ($entitiesFields as $entityTypeId => $fields): ?>
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
							$dialog->renderFieldControl(
								$field,
								$dialog->getCurrentValue($field, $chosenEntityValues[$fieldId]),
								true,
								\Bitrix\Bizproc\FieldType::RENDER_MODE_DESIGNER
							);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>
	</td>
</tr>

<tr hidden>
	<td width="60%">
		<?= $dialog->renderFieldControl(
			$dialog->getMap()['OnlyDynamicEntities'],
			$dialog->getCurrentValue('only_dynamic_entities'),
			false,
			1
		) ?>
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
