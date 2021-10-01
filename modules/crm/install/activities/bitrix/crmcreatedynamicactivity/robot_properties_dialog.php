<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
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
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($dynamicTypeIdField['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($dynamicTypeIdField, $dialog->getCurrentValue($dynamicTypeIdField))?>
</div>

<?php foreach ($dynamicEntitiesFields as $entityTypeId => $fields): ?>
	<div id="ccda-fields-map-<?= $entityTypeId ?>" <?= $entityTypeId !== $chosenEntityTypeId ? 'hidden' : ''?>>
		<?php foreach ($fields as $fieldId => $field): ?>
			<div class="bizproc-automation-popup-settings">
				<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
					<?=htmlspecialcharsbx($field['Name'])?>:
				</span>
				<?=$dialog->renderFieldControl($field, $chosenEntityValues[$fieldId])?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endforeach; ?>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmCreateDynamicActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			fieldsContainerIdPrefix: 'ccda-fields-map-',
		});
		script.init();
	})
</script>