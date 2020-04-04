<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Conversion\EntityConverter;
use Bitrix\Crm\Category\DealCategory;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmQuoteDetailsComponent $component */

$guid = $arResult['GUID'];
$prefix = strtolower($guid);

$APPLICATION->IncludeComponent(
	'bitrix:crm.quote.menu',
	'',
	array(
		'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
		'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'TYPE' => 'details',
		'SCRIPTS' => array(
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processRemoval();'
		)
	),
	$component
);

?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.message({ "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_QUOTE_DETAIL_HISTORY_STUB')?>" });
			}
		);
</script><?

$editorContext = array('PARAMS' => $arResult['CONTEXT_PARAMS']);
if(isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '')
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	array(
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => \CCrmOwnerType::Quote,
		'ENTITY_ID' => $arResult['COMPONENT_MODE'] === ComponentMode::MODIFICATION ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['COMPONENT_MODE'] === ComponentMode::VIEW,
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.details/ajax.php?action=convert&'.bitrix_sessid_get(),
		'EDITOR' => array(
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'DUPLICATE_CONTROL' => $arResult['DUPLICATE_CONTROL'],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
			'USER_FIELD_ENTITY_ID' => $arResult['USER_FIELD_ENTITY_ID'],
			'USER_FIELD_CREATE_PAGE_URL' => $arResult['USER_FIELD_CREATE_PAGE_URL'],
			'USER_FIELD_CREATE_SIGNATURE' => $arResult['USER_FIELD_CREATE_SIGNATURE'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext
		),
		'TIMELINE' => array(
			'GUID' => "{$guid}_timeline",
			'ENABLE_WAIT' => false,
			'ENABLE_CALL' => false,
			'ENABLE_MEETING' => false,
			'ENABLE_EMAIL' => false,
			'ENABLE_TASK' => false,
			'ENABLE_VISIT' => false,
			'PROGRESS_SEMANTICS' => $arResult['PROGRESS_SEMANTICS']
		),
		'ENABLE_PROGRESS_BAR' => true,
		'ENABLE_PROGRESS_CHANGE' => $arResult['ENABLE_PROGRESS_CHANGE'],
		//'ACTIVITY_EDITOR_ID' => '',
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
		//'CAN_CONVERT' => isset($arResult['CAN_CONVERT']) ? $arResult['CAN_CONVERT'] : false,
		//'CONVERSION_SCHEME' => isset($arResult['CONVERSION_SCHEME']) ? $arResult['CONVERSION_SCHEME'] : null
	)
);

if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):
	?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityType.captions =
				{
					<?=CCrmOwnerType::LeadName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
					<?=CCrmOwnerType::ContactName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
					<?=CCrmOwnerType::CompanyName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
					<?=CCrmOwnerType::DealName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
					<?=CCrmOwnerType::InvoiceName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
					<?=CCrmOwnerType::QuoteName?>: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
				};

				BX.CrmQuoteConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\QuoteConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmQuoteConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_QUOTE_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_QUOTE_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmQuoteConverter.permissions =
				{
					deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>,
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>
				};
				BX.CrmQuoteConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.quote.details/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
				BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
					DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
				)?>;
				BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_QUOTE_EDIT_CONV_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_QUOTE_EDIT_CONV_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_QUOTE_EDIT_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_QUOTE_EDIT_BUTTON_CANCEL')?>"
				};
			}
		);
	</script><?
endif;?>
