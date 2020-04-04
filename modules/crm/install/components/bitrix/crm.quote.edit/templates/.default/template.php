<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

use \Bitrix\Crm\Conversion\EntityConverter;
use \Bitrix\Crm\Category\DealCategory;

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if(isset($arResult['CONVERSION_LEGEND'])):
	?><div class="crm-view-message"><?=$arResult['CONVERSION_LEGEND']?></div><?
endif;

$jsCoreInit = array('date', 'popup', 'ajax');
if($arResult['ENABLE_DISK'])
{
	$jsCoreInit[] = 'uploader';
	$jsCoreInit[] = 'file_dialog';
}
CJSCore::Init($jsCoreInit);

if($arResult['ENABLE_DISK'])
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/disk_uploader.js');
	$APPLICATION->SetAdditionalCSS('/bitrix/js/disk/css/legacy_uf_common.css');
}
if($arResult['ENABLE_WEBDAV'])
{
	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav.user.field/templates/.default/style.css');
	$APPLICATION->SetAdditionalCSS('/bitrix/js/webdav/css/file_dialog.css');

	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/core/core_dd.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/file_upload_agent.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/webdav/file_dialog.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/webdav_uploader.js');
}

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	($elementID > 0) ? 'CRM_QUOTE_SHOW_TITLE' : 'CRM_QUOTE_SHOW_NEW_TITLE',
	array(
		'#QUOTE_NUMBER#' => !empty($arResult['ELEMENT']['QUOTE_NUMBER']) ? $arResult['ELEMENT']['QUOTE_NUMBER'] : '-',
		'#BEGINDATE#' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : '-'
	)
);

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_product_rows'):
		$productFieldset['NAME'] = $field['name'];
		unset($arTabs[0]['fields'][$k]);
	endif;
	if($field['id'] === 'PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;
endforeach;
unset($field);

$standardButtonsTitles = array();
if (!empty($arResult['QUOTE_REFERER']))
	$standardButtonsTitles['saveAndView']['title'] = GetMessage('CRM_QUOTE_CUSTOM_SAVE_BUTTON_TITLE');

$arFormButtons = array(
	'back_url' => $arResult['BACK_URL'],
	'standard_buttons_titles' => $standardButtonsTitles,
	'standard_buttons' => true,
	'wizard_buttons' => false,
	'custom_html' => '<input type="hidden" name="quote_id" value="'.$elementID.'"/>'
);

if(isset($arResult['DEAL_ID']) && $arResult['DEAL_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="deal_id" value="'.$arResult['DEAL_ID'].'"/>';
}
elseif(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['dialog_buttons'] = true;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="external_context" value="'.htmlspecialcharsbx($arResult['EXTERNAL_CONTEXT']).'"/>';
}

$arFormButtons['custom_html'] .= $arResult['FORM_CUSTOM_HTML'];

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'FIELD_SETS' => array($productFieldset),
		'USER_FIELD_ENTITY_ID' => CCrmQuote::$sUFEntityID,
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'BUTTONS' => $arFormButtons,
		'IS_NEW' => $elementID <= 0,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);

$prefixLower = strtolower($arResult['PREFIX']);
$companySpecifiedClientFields = array('CLIENT_CONTACT', 'CLIENT_TP_ID');
if (LANGUAGE_ID === 'ru')
	$companySpecifiedClientFields[] = 'CLIENT_TPA_ID';
$editorSettings = array(
	'formId' => $arResult['FORM_ID'],
	'productRowEditorId' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'filesFieldSettings' => array(
		'containerId' => $arResult['FILES_FIELD_CONTAINER_ID'],
		'controlMode' => 'edit',
		'webDavSelectUrl' => $arResult['WEBDAV_SELECT_URL'],
		'webDavUploadUrl' => $arResult['WEBDAV_UPLOAD_URL'],
		'webDavShowUrl' => $arResult['WEBDAV_SHOW_URL'],
		'files' => $arResult['ELEMENT']['STORAGE_ELEMENT_IDS'],
		'uploadContainerID' => $prefixLower.'_upload_container',
		'uploadControlID' => $prefixLower.'_uploader',
		'uploadInputID' => $prefixLower.'_saved_file',
		'storageTypeId' => $arResult['ELEMENT']['STORAGE_TYPE_ID'],
		'defaultStorageTypeId' => CCrmQuote::GetDefaultStorageTypeID(),
		'serviceUrl' => '/bitrix/components/bitrix/crm.quote.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'messages' => array(
			'webdavFileLoading' => GetMessage('CRM_QUOTE_WEBDAV_FILE_LOADING'),
			'webdavFileAlreadyExists' => GetMessage('CRM_QUOTE_WEBDAV_FILE_ALREADY_EXISTS'),
			'webdavFileAccessDenied' => GetMessage('CRM_QUOTE_WEBDAV_FILE_ACCESS_DENIED'),
			'webdavAttachFile' => GetMessage('CRM_QUOTE_WEBDAV_ATTACH_FILE'),
			'webdavTitle' => GetMessage('CRM_QUOTE_WEBDAV_TITLE'),
			'webdavDragFile' => GetMessage('CRM_QUOTE_WEBDAV_DRAG_FILE'),
			'webdavSelectFile' => GetMessage('CRM_QUOTE_WEBDAV_SELECT_FILE'),
			'webdavSelectFromLib' => GetMessage('CRM_QUOTE_WEBDAV_SELECT_FROM_LIB'),
			'webdavLoadFiles' => GetMessage('CRM_QUOTE_WEBDAV_LOAD_FILES'),
			'diskAttachFiles' => GetMessage('CRM_QUOTE_DISK_ATTACH_FILE'),
			'diskAttachedFiles' => GetMessage('CRM_QUOTE_DISK_ATTACHED_FILES'),
			'diskSelectFile' => GetMessage('CRM_QUOTE_DISK_SELECT_FILE'),
			'diskSelectFileLegend' => GetMessage('CRM_QUOTE_DISK_SELECT_FILE_LEGEND'),
			'diskUploadFile' => GetMessage('CRM_QUOTE_DISK_UPLOAD_FILE'),
			'diskUploadFileLegend' => GetMessage('CRM_QUOTE_DISK_UPLOAD_FILE_LEGEND')
		)
	)
);

CCrmQuote::PrepareStorageElementInfo($arResult['ELEMENT']);
if(isset($arResult['ELEMENT']['WEBDAV_ELEMENTS']))
{
	$editorSettings['filesFieldSettings']['webdavelements'] = $arResult['ELEMENT']['WEBDAV_ELEMENTS'];
}
elseif(isset($arResult['ELEMENT']['DISK_FILES']))
{
	$editorSettings['filesFieldSettings']['diskfiles'] = $arResult['ELEMENT']['DISK_FILES'];
}

?><script type="text/javascript">

	window.CrmProductRowSetLocation = function(){ BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']); };

	BX.ready(function(){
		BX.CrmQuoteEditor.create(
			"<?=strtolower($arResult['FORM_ID'])?>",
			<?=CUtil::PhpToJSObject($editorSettings)?>
		);
	});
</script>
<?if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityType.captions =
				{
					"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
					"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
					"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
					"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
					"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
					"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
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
					serviceUrl: "<?='/bitrix/components/bitrix/crm.quote.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
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
	</script>
<?endif;?>