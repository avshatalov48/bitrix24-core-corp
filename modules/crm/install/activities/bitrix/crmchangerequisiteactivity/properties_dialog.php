<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangerequisiteactivity/script.js'));

include ($dialog->getRuntimeData()['PathToParentClassDir'] . DIRECTORY_SEPARATOR . 'properties_dialog.php');

CJSCore::Init('bp_field_type')
?>

<tr id="ccra_pd_list_form">
	<td colspan="2">
		<table width="100%" border="0" cellpadding="2" cellspacing="2" id="bca_ccra_requisite_fields">
		</table>
		<a href="#" id="id_bca_ccra_add_condition"><?= GetMessage('CRM_CRA_ADD_CONDITION') ?></a>
		<span id="bwfvc_container"></span>
	</td>
</tr>

<script>
	BX.ready(function()
	{
		BX.Crm.Activity.CrmChangeRequisiteActivity.init({
			requisiteFieldsNodeId: 'bca_ccra_requisite_fields',
			addConditionLinkId: 'id_bca_ccra_add_condition',
			documentType: <?= \Bitrix\Main\Web\Json::encode($dialog->getDocumentType()) ?>,

			requisiteFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['RequisiteFieldsMap']) ?>,
			bankDetailFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['BankDetailFieldsMap']) ?>,
			addressFieldsMap: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['AddressFieldsMap']) ?>,

			formName: "<?= $dialog->getFormName() ?>",
			currentValues: <?= \Bitrix\Main\Web\Json::encode($dialog->getCurrentValues()['FieldsValues']) ?>,

			presetFieldNames: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['PresetRequisiteFieldNames']) ?>,
			selectPresetNodeId: "id_" + "<?=$dialog->getMap()['RequisitePresetId']['FieldName']?>",
			messages: {
				"CRM_CRA_DELETE_CONDITION": "<?= GetMessage('CRM_CRA_DELETE_CONDITION') ?>"
			}
		});
	});
</script>
