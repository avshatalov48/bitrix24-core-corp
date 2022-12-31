<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.buttons',
	'ui.hint',
	'ui.notification',
	'ui.alerts',
	'ui.dialogs.messagebox',
	'ui.entity-selector',
]);

$messages = array_merge(
	Loc::loadLanguageFile(
		\Bitrix\Main\Application::getDocumentRoot()
		. Path::normalize('/bitrix/components/bitrix/bizproc.automation/templates/.default/template.php')
	),
	Loc::loadLanguageFile(
		\Bitrix\Main\Application::getDocumentRoot()
		. Path::normalize('/bitrix/components/bitrix/bizproc.workflow.edit/templates/.default/template.php')
	)
);
Asset::getInstance()->addJs(Path::normalize('/bitrix/activities/bitrix/crmupdatedynamicactivity/script.js'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
?>

<?php
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:bizproc.automation',
	'',
	[
		'API_MODE' => 'Y',
		'DOCUMENT_TYPE' => $dialog->getDocumentType(),
	]
);
?>

<?php foreach ($map as $field): ?>
	<?php if (isset($field['Name'], $field['Type'])): ?>
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
	<?php endif; ?>
<?php endforeach; ?>

<tr data-role="bca-cuda-entity-type-id-dependent">
	<td align="right" width="40%"><?=$map['DynamicFilterFields']['Name']?>:</td>
	<td width="60%">
		<div data-role="bca-cuda-filter-fields-container"></div>
	</td>
</tr>

<tr data-role="bca-cuda-entity-type-id-dependent">
	<td colspan="2">
		<table width="100%" border="0" cellpadding="2" cellspacing="2" data-role="bca-cuda-fields-container">
		</table>
		<a href="#" data-role="bca_cuda_add_condition"><?= GetMessage('CRM_UDA_ADD_CONDITION') ?></a>
		<span id="bwfvc_container"></span>
	</td>
</tr>

<script>
	BX.ready(function()
	{
		BX.message(<?=Json::encode($messages)?>);
		BX.message(<?=Json::encode(Loc::loadLanguageFile($dialog->getActivityFile())) ?>);

		var script = new BX.Crm.Activity.CrmUpdateDynamicActivity({
			documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
			documentName: '<?= CUtil::JSEscape($dialog->getRuntimeData()['DocumentName']) ?>',
			documentFields: <?= Json::encode($dialog->getRuntimeData()['DocumentFields']) ?>,
			isRobot: false,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			fieldsMap: <?= Json::encode($dialog->getMap()['DynamicEntitiesFields']['Map']) ?>,

			filteringFieldsPrefix: '<?=CUtil::JSEscape($map['DynamicFilterFields']['FieldName'])?>_',
			filterFieldsMap: <?=Json::encode($map['DynamicFilterFields']['Map'])?>,
			conditions: <?=Json::encode($dialog->getCurrentValue('dynamic_filter_fields'))?>,

			currentValues: <?= Json::encode($dialog->getCurrentValue('dynamic_entities_fields')) ?>,
		});
		script.init();
		script.render();
	})
</script>
