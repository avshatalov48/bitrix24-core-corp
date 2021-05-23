<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangerequisiteactivity/script.js'));

include ($dialog->getRuntimeData()['PathToParentClassDir'] . DIRECTORY_SEPARATOR . 'robot_properties_dialog.php');
?>

<div id="bca_ccra_requisite_fields">
	<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text">
		<a class="bizproc-automation-popup-settings-link" data-role="bca-ccra-fields-list">
			<?= GetMessage('CRM_CRA_RBP_ADD_CONDITION') ?>
		</a>
	</div>
</div>

<?= $dialog->getRuntimeData()['javascriptFunctions'] ?>
<script>
	BX.ready(function()
	{
		BX.Crm.Activity.CrmChangeRequisiteActivity.init({
			requisiteFieldsNodeId: 'bca_ccra_requisite_fields',
			addConditionLinkId: 'bca_ccra_add_condition',
			isRobot: true,
			documentType: <?= \Bitrix\Main\Web\Json::encode($dialog->getDocumentType()) ?>,
			requisiteFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['RequisiteFieldsMap']) ?>,
			bankDetailFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['BankDetailFieldsMap']) ?>,
			addressFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['AddressFieldsMap']) ?>,
			formName: "<?= $dialog->getFormName() ?>",
			currentValues: <?= \Bitrix\Main\Web\Json::encode($dialog->getCurrentValues()['FieldsValues']) ?>,

			selectPresetNodeId: "id_" + "<?=$dialog->getMap()['RequisitePresetId']['FieldName']?>",
			presetFieldNames: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['PresetRequisiteFieldNames']) ?>,
			messages: {
				"CRM_CRA_DELETE_CONDITION": "<?= GetMessageJS('CRM_CRA_RBP_DELETE_CONDITION') ?>"
			}
		});
	});
</script>
