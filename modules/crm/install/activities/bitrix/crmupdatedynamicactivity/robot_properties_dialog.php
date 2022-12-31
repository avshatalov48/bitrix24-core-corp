<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmupdatedynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
?>
<?php foreach ($map as $field): ?>
	<?php if (isset($field['Name'], $field['Type'])): ?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
				<?=htmlspecialcharsbx($field['Name'])?>:
			</span>
			<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
		</div>
	<?php endif; ?>
<?php endforeach;?>

<div data-role="bca-cuda-entity-type-id-dependent">
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?=$map['DynamicFilterFields']['Name']?>:
		</span>
		<div data-role="bca-cuda-filter-fields-container"></div>
	</div>
</div>

<div data-role="bca-cuda-entity-type-id-dependent">
	<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text">
		<a class="bizproc-automation-popup-settings-link" data-role="bca-cuda-fields-list">
			<?= GetMessage('CRM_UDA_ADD_CONDITION') ?>
		</a>
	</div>
	<div data-role="bca-cuda-fields-container"></div>
</div>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode(Loc::loadLanguageFile($dialog->getActivityFile())) ?>);

		var script = new BX.Crm.Activity.CrmUpdateDynamicActivity({
			documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
			isRobot: true,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			fieldsMap: <?= Json::encode($map['DynamicEntitiesFields']['Map']) ?>,

			filteringFieldsPrefix: '<?=CUtil::JSEscape($map['DynamicFilterFields']['FieldName'])?>_',
			filterFieldsMap: <?=Json::encode($map['DynamicFilterFields']['Map'])?>,
			conditions: <?=Json::encode($dialog->getCurrentValue('dynamic_filter_fields'))?>,

			currentValues: <?= Json::encode($dialog->getCurrentValue('dynamic_entities_fields')) ?>
		});
		script.init();
		script.render();
	})
</script>
