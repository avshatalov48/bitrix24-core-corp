<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
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
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($typeIdField['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($typeIdField, $dialog->getCurrentValue($typeIdField))?>
</div>

<div id="fields-map-container"></div>

<div hidden>
	<?= $dialog->renderFieldControl($dialog->getMap()['OnlyDynamicEntities']) ?>
</div>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmCreateDynamicActivity({
			isRobot: true,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			entitiesFieldsMap: <?= \Bitrix\Main\Web\Json::encode($entitiesFields) ?>,
			currentValues: <?= \Bitrix\Main\Web\Json::encode($chosenEntityValues) ?>,
		});
		script.init();
	})
</script>