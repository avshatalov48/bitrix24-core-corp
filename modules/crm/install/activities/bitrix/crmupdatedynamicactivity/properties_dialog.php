<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
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

<tr>
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
		BX.message(<?= Json::encode(Loc::loadLanguageFile($dialog->getActivityFile())) ?>);

		var script = new BX.Crm.Activity.CrmUpdateDynamicActivity({
			documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
			isRobot: false,
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
			fieldsMap: <?= Json::encode($dialog->getMap()['DynamicEntitiesFields']['Map']) ?>,
			currentValues: <?= Json::encode($dialog->getCurrentValue('dynamic_entities_fields')) ?>,
		});
		script.init();
	})
</script>
