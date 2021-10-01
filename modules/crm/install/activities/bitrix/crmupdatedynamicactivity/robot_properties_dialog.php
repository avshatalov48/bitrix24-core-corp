<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmupdatedynamicactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>
<?php foreach ($dialog->getMap() as $field): ?>
	<?php if (array_key_exists('Name', $field)): ?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
				<?=htmlspecialcharsbx($field['Name'])?>:
			</span>
			<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
		</div>
	<?php endif; ?>
<?php endforeach;?>

<div>
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
			fieldsMap: <?= Json::encode($dialog->getMap()['DynamicEntitiesFields']['Map']) ?>,
			currentValues: <?= Json::encode($dialog->getCurrentValue('dynamic_entities_fields')) ?>
		});
		script.init();
	})
</script>
