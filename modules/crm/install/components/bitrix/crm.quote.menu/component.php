<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global CDatabase $DB
 */

use Bitrix\Crm\Integration\DocumentGenerator;
use Bitrix\Crm\Integration\DocumentGeneratorManager;

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmQuote::CheckReadPermission(0, $CrmPerms))
{
	return;
}

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_QUOTE_DETAILS'] = CrmCheckPath('PATH_TO_QUOTE_DETAILS', $arParams['PATH_TO_QUOTE_DETAILS'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&details');
$arParams['PATH_TO_QUOTE_KANBAN'] = CrmCheckPath('PATH_TO_QUOTE_KANBAN', $arParams['PATH_TO_QUOTE_KANBAN'], $APPLICATION->GetCurPage()."?kanban");

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_quote_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'EXPORT');
	//$bImport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$bExport = false;
	//$bImport = false;

	$bRead   = CCrmQuote::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmQuote::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmQuote::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmQuote::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

//Skip COPY menu in slider mode
/*
if($arParams['TYPE'] == 'copy' && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
{
	return false;
}
*/

if($arParams['TYPE'] === 'details')
{
	if($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : array();

	CCrmQuote::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CrmPerms);
	if($arResult['CAN_CONVERT'])
	{
		$schemeID = \Bitrix\Crm\Conversion\QuoteConversionConfig::getCurrentSchemeID();
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getConversionRestriction();
		$isPermitted = $restriction->hasPermission();
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'toolbar-conv-scheme',
			'PARAMS' => array(
				'NAME' => 'quote_converter',
				'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
				'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
				'ENTITY_ID' => $arParams['ELEMENT_ID'],
				'SCHEME_ID' => $schemeID,
				'SCHEME_NAME' => \Bitrix\Crm\Conversion\QuoteConversionScheme::resolveName($schemeID),
				'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\QuoteConversionScheme::getDescription($schemeID),
				'IS_PERMITTED' => $isPermitted,
				'LOCK_SCRIPT' => $isPermitted ? '' : $restriction->preparePopupScript()
			),
			'CODE' => 'convert',
			'TEXT' => GetMessage('QUOTE_CREATE_ON_BASIS'),
			'TITLE' => GetMessage('QUOTE_CREATE_ON_BASIS_TITLE'),
			'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_QUOTE_DETAILS'],
				array('quote_id' => $arParams['ELEMENT_ID'])
			),
			array('copy' => 1)
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_COPY'),
			'TITLE' => GetMessage('QUOTE_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_DELETE'),
			'TITLE' => GetMessage('QUOTE_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	$this->IncludeComponentTemplate();
	return;
}

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_ADD'),
			'TITLE' => GetMessage('QUOTE_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
				array(
					'quote_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	/*if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_IMPORT'),
			'TITLE' => GetMessage('QUOTE_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}*/

	if ($bExport)
	{
		if($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}

		$entityType = CCrmOwnerType::QuoteName;
		$stExportId = 'STEXPORT_'.$entityType.'_MANAGER';
		$randomSequence = new Bitrix\Main\Type\RandomSequence($stExportId);
		$stExportManagerId = $stExportId.'_'.$randomSequence->randString();
		$componentName = 'bitrix:crm.quote.list';

		$arResult['STEXPORT_PARAMS'] = array(
			'componentName' => $componentName,
			'siteId' => SITE_ID,
			'entityType' => $entityType,
			'stExportId' => $stExportId,
			'managerId' => $stExportManagerId,
			'sToken' => 's'.time(),
			'initialOptions' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => GetMessage('QUOTE_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
				'EXPORT_PRODUCT_FIELDS' => array(
					'name' => 'EXPORT_PRODUCT_FIELDS',
					'type' => 'checkbox',
					'title' => GetMessage('QUOTE_STEXPORT_OPTION_EXPORT_PRODUCT_FIELDS'),
					'value' => 'N'
				),
			),
			'componentParams' => \Bitrix\Main\Component\ParameterSigner::signParameters($componentName, array(
				'QUOTE_COUNT' => '20',
				'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'],
				'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'],
				'PATH_TO_QUOTE_KANBAN' => $arResult['PATH_TO_QUOTE_KANBAN'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $entityType
			)),
			'messages' => array(
				'stExportExcelDlgTitle' => GetMessage('QUOTE_EXPORT_EXCEL_TITLE'),
				'stExportExcelDlgSummary' => GetMessage('QUOTE_STEXPORT_SUMMARY'),
				'stExportCsvDlgTitle' => GetMessage('QUOTE_EXPORT_CSV_TITLE'),
				'stExportCsvDlgSummary' => GetMessage('QUOTE_STEXPORT_SUMMARY')
			)
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('QUOTE_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('QUOTE_EXPORT_CSV'),
			'ONCLICK' => "BX.Crm.ExportManager.items['".CUtil::JSEscape($stExportManagerId)."'].startExport('csv')",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('QUOTE_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('QUOTE_EXPORT_EXCEL'),
			'ONCLICK' => "BX.Crm.ExportManager.items['".CUtil::JSEscape($stExportManagerId)."'].startExport('excel')",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		unset($entityType, $stExportId, $randomSequence, $stExportManagerId);
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& CCrmQuote::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $CrmPerms))
{
	$schemeID = \Bitrix\Crm\Conversion\QuoteConversionConfig::getCurrentSchemeID();
	$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getConversionRestriction();
	$isPermitted = $restriction->hasPermission();
	$arResult['BUTTONS'][] = array(
		'TYPE' => 'toolbar-conv-scheme',
		'PARAMS' => array(
			'NAME' => 'quote_converter',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
			'ENTITY_ID' => $arParams['ELEMENT_ID'],
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\QuoteConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\QuoteConversionScheme::getDescription($schemeID),
			'IS_PERMITTED' => $isPermitted,
			'LOCK_SCRIPT' => $isPermitted ? '' : $restriction->preparePopupScript()
		),
		'CODE' => 'convert',
		'TEXT' => GetMessage('QUOTE_CREATE_ON_BASIS'),
		'TITLE' => GetMessage('QUOTE_CREATE_ON_BASIS_TITLE'),
		'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
	);
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']))
{
	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'CODE' => 'document',
			'TEXT' => GetMessage('QUOTE_DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('QUOTE_DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(DocumentGenerator\DataProvider\Quote::class, $arParams['ELEMENT_ID']),
		];
	}

	if (CCrmQuote::isPrintingViaPaymentMethodSupported())
	{
		$menuItems = [];
		$menuItems[] = [
			'text' => GetMessage('QUOTE_PAYMENT_HTML'),
			'title' => GetMessage('QUOTE_PAYMENT_HTML_TITLE'),
			'onclick' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: false }])"
		];
		$menuItems[] = [
			'text' => GetMessage('QUOTE_PAYMENT_HTML_BLANK'),
			'title' => GetMessage('QUOTE_PAYMENT_HTML_BLANK_TITLE'),
			'onclick' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: true }])"
		];
		if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
		{
			$menuItems[] = [
				'text' => GetMessage('QUOTE_PAYMENT_PDF'),
				'title' => GetMessage('QUOTE_PAYMENT_PDF_TITLE'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: false }])"
			];
			$menuItems[] = [
				'text' => GetMessage('QUOTE_PAYMENT_PDF_BLANK'),
				'title' => GetMessage('QUOTE_PAYMENT_PDF_BLANK_TITLE'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: true }])"
			];
			$menuItems[] = [
				'text' => GetMessage('QUOTE_PAYMENT_EMAIL'),
				'title' => GetMessage('QUOTE_PAYMENT_EMAIL_TITLE'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteSendByEmail', [this])"
			];
		}
		if (!empty($menuItems))
		{
			$arResult['BUTTONS'][] = [
				'CODE' => 'leftMenu',
				'TEXT' => GetMessage('QUOTE_LEFT_MENU_TEXT'),
				'TITLE' => GetMessage('QUOTE_LEFT_MENU_TITLE'),
				'TYPE' => 'toolbar-menu-left',
				'ITEMS' => $menuItems,
			];
		}
		unset($menuItems);
	}

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_EDIT'),
			'TITLE' => GetMessage('QUOTE_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
				array(
					'quote_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_SHOW'),
		'TITLE' => GetMessage('QUOTE_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_COPY'),
		'TITLE' => GetMessage('QUOTE_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_DELETE'),
		'TITLE' => GetMessage('QUOTE_DELETE_TITLE'),
		'LINK' => "javascript:quote_delete('".GetMessage('QUOTE_DELETE_DLG_TITLE')."', '".GetMessage('QUOTE_DELETE_DLG_MESSAGE')."', '".GetMessage('QUOTE_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_ADD'),
		'TITLE' => GetMessage('QUOTE_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => 0
			)
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>