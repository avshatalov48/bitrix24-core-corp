<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var array $arResult */
$formParams = $arResult['FORM_PARAMS'];
$jsParams = $arResult['JS_PARAMS'];

/** @global CMain $APPLICATION */
global $APPLICATION;
$APPLICATION->setTitle($formParams['FORM_TITLE']);

if($this->getComponent()->hasErrors())
{
	foreach($this->getComponent()->getErrors() as $errMsg)
	{
		ShowError($errMsg);
	}

	return;
}

Extension::load([
	'ui.design-tokens',
	'ls',
	'crm_common',
	'crm.entity-editor.manager.duplicates',
	'crm.entity-editor.field.address.ui',
	'crm.entity-editor.field.bank-details',
	'crm.entity-editor.field.requisite-autocomplete',
	'crm.entity-editor.field.image',
	'crm.entity-editor.field-attr',
]);

$requisiteFields = [];
$bankDetailsFields = [];

foreach ($formParams['FIELDS'] as $field)
{
	if ($field['type'] == 'bankDetails')
	{
		$bankDetailsFields[] = ['name' => $field['name']];
	}
	else
	{
		$requisiteFields[] = ['name' => $field['name']];
	}
}

$APPLICATION->IncludeComponent(
	"bitrix:ui.form", "",
	[
		'INITIAL_MODE' => 'edit',
		'GUID' => $formParams['FORM_ID'],
		'CONFIG_ID' => $formParams['CONFIG_ID'],
		'ENTITY_ID' => $arResult['REQUISITE_ID'],
		'ENTITY_TYPE_NAME' => CCrmOwnerType::RequisiteName,
		'ENTITY_FIELDS' => $formParams['FIELDS'],
		'ENABLE_CONFIGURATION_UPDATE' => $formParams['~ENABLE_CONFIGURATION_UPDATE'] ?? null,
		'ENABLE_COMMON_CONFIGURATION_UPDATE' => $formParams['~ENABLE_COMMON_CONFIGURATION_UPDATE'] ?? null,
		'ENABLE_PERSONAL_CONFIGURATION_UPDATE' => $formParams['~ENABLE_PERSONAL_CONFIGURATION_UPDATE'] ?? null,
		'ENTITY_CONFIG' => [
			[
				'name' => 'REQUISITES_SECTION',
				'title' => Loc::getMessage(
					'CRM_REQUISITE_DETAILS_REQUISITE_SECTION_TITLE_'.$arResult['ENTITY_TYPE_MNEMO']
				),
				'type' => 'section',
				'enableToggling' => false,
				'data' => [
					'isRemovable' => false,
					'enableToggling' => false
				],
				'elements' => $requisiteFields
			],
			[
				'name' => 'BANK_REQUISITES_SECTION',
				'title' => Loc::getMessage('CRM_REQUISITE_DETAILS_BANK_DETAILS_SECTION_TITLE'),
				'type' => 'section',
				'enableToggling' => false,
				'isDragEnabled' => false,
				'data' => [
					'isRemovable' => false,
					'enableToggling' => false,
					'isChangeable' => false
				],
				'elements' => $bankDetailsFields
			],
		],
		'ENTITY_DATA' => $formParams['DATA'],
		'IS_IDENTIFIABLE_ENTITY' => false,
		'ENABLE_AJAX_FORM' => true,
		'ENABLE_USER_FIELD_CREATION' => false,
		'USER_FIELD_CREATE_SIGNATURE' => $formParams['USER_FIELD_CREATE_SIGNATURE'],
		'USER_FIELD_ENTITY_ID' => $formParams['USER_FIELD_ENTITY_ID'],
		'READ_ONLY' => $formParams['READ_ONLY'],
		'CONTEXT' => $formParams['CONTEXT'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.details/slider.ajax.php',
		'ENABLE_FIELDS_CONTEXT_MENU' => true,
		'ENABLE_SHOW_ALWAYS_FEAUTURE' => false,
	]
);
?>

<script>
	BX.ready(
		function()
		{
			BX.Crm.RequisiteDetailsManager.create(<?= CUtil::PhpToJSObject($jsParams) ?>);
			BX.CrmEntityType.categoryCaptions = <?=CUtil::PhpToJSObject(\CCrmOwnerType::GetAllCategoryCaptions(true))?>;
			BX.message(
				{
					"CRM_EDITOR_PLACEMENT_CAUTION": "<?=GetMessageJS('CRM_REQUISITE_DETAILS_PLACEMENT_CAUTION')?>"
				}
			);
		});
</script>