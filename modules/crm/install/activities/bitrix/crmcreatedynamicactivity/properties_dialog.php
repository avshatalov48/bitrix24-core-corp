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

$entitiesFields = [];
foreach ($dialog->getMap()['DynamicEntitiesFields']['Map'] as $entityTypeId => $fieldsMap)
{
	$entitiesFields[$entityTypeId] = [
		'documentType' => CCrmBizProcHelper::ResolveDocumentType($entityTypeId),
		'fieldsMap' => $fieldsMap,
	];
}
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
		<div id="fields-map-container"></div>
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
			isRobot: false,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			entitiesFieldsMap: <?= \Bitrix\Main\Web\Json::encode($entitiesFields) ?>,
			currentValues: <?= \Bitrix\Main\Web\Json::encode($chosenEntityValues) ?>,
		});
		script.init();
	});
</script>
