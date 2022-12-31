<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmgetdynamicinfoactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
$returnFieldsProperty = $map['ReturnFields'];
unset($map['ReturnFields'], $returnFieldsProperty['Map'], $returnFieldsProperty['Getter']);
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
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?=$dialog->getMap()['ReturnFields']['Name']?>:
		</span>
		<div data-role="bca-cuda-return-fields-container"></div>
	</div>
</div>
<div hidden>
	<?= $dialog->renderFieldControl($dialog->getMap()['OnlyDynamicEntities']) ?>
</div>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode(Loc::loadLanguageFile($dialog->getActivityFile())) ?>);

		var script = new BX.Crm.Activity.CrmGetDynamicInfoActivity({
			documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
			isRobot: true,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			returnFieldsProperty: <?=Json::encode($returnFieldsProperty)?>,
			returnFieldsIds: <?=Json::encode($dialog->getCurrentValue('return_fields'))?>,
			returnFieldsMap: <?=Json::encode($dialog->getMap()['ReturnFields']['Map'])?>,

			filteringFieldsPrefix: '<?=CUtil::JSEscape($map['DynamicFilterFields']['FieldName'])?>_',
			filterFieldsMap: <?=Json::encode($map['DynamicFilterFields']['Map'])?>,
			conditions: <?=Json::encode($dialog->getCurrentValue('dynamic_filter_fields'))?>,
		});
		script.init();
	})
</script>
