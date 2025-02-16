<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 */

use Bitrix\Crm\Component\EntityList\Settings\PermissionItem;
use Bitrix\Crm\Integration\DocumentGenerator;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Main\Localization\Loc;

if (!CModule::IncludeModule('crm'))
{
	return;
}

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmQuote::CheckReadPermission(0, $CrmPerms))
{
	return;
}

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath(
	'PATH_TO_QUOTE_LIST',
	$arParams['PATH_TO_QUOTE_LIST'] ?? '',
	$APPLICATION->GetCurPage()
);

$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath(
	'PATH_TO_QUOTE_SHOW',
	$arParams['PATH_TO_QUOTE_SHOW'] ?? '',
	$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&show'
);

$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
	'PATH_TO_QUOTE_EDIT',
	$arParams['PATH_TO_QUOTE_EDIT'] ?? '',
	$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&edit'
);

$arParams['PATH_TO_QUOTE_DETAILS'] = CrmCheckPath(
	'PATH_TO_QUOTE_DETAILS',
	$arParams['PATH_TO_QUOTE_DETAILS'] ?? '',
	$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&details'
);

$arParams['PATH_TO_QUOTE_KANBAN'] = CrmCheckPath(
	'PATH_TO_QUOTE_KANBAN',
	$arParams['PATH_TO_QUOTE_KANBAN'] ?? '',
	$APPLICATION->GetCurPage() . "?kanban"
);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)($arParams['ELEMENT_ID']) : 0;

$makeEditPathFromDetailsPath = static function(string $pathToDetails): string
{
	$templateWithoutConflictingSymbols = str_replace('#quote_id#', 'quote_id', $pathToDetails);
	$editWithoutConflictingSymbols = CHTTP::urlAddParams($templateWithoutConflictingSymbols, ['init_mode' => 'edit']);

	return str_replace('quote_id', '#quote_id#', $editWithoutConflictingSymbols);
};

if (\CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Quote))
{
	$arParams['PATH_TO_QUOTE_SHOW'] = $arParams['PATH_TO_QUOTE_DETAILS'];
	$arParams['PATH_TO_QUOTE_EDIT'] = $makeEditPathFromDetailsPath($arParams['PATH_TO_QUOTE_DETAILS']);
}

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

if (isset($_REQUEST['copy']))
{
	$arParams['TYPE'] = 'copy';
}

$toolbarID = 'toolbar_quote_'.$arParams['TYPE'];

if (isset($arParams['ELEMENT_ID']) && $arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_' . $arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = [];
$isInSlider = isset($arParams['IN_SLIDER']) && $arParams['IN_SLIDER'] === 'Y';

if ($arParams['TYPE'] === 'list')
{
	$bRead   = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'EXPORT');
	//$bImport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE');
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
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
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] === 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
{
	return false;
}

//Skip COPY menu in slider mode
/*
if ($arParams['TYPE'] == 'copy' && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
{
	return false;
}
*/

if ($arParams['TYPE'] === 'details')
{
	if ($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : [];

	CCrmQuote::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CrmPerms);
	if ($arResult['CAN_CONVERT'])
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
				'LOCK_SCRIPT' => $isPermitted ? '' : $restriction->prepareInfoHelperScript()
			),
			'CODE' => 'convert',
			'TEXT' => Loc::getMessage('QUOTE_CREATE_ON_BASIS'),
			'TITLE' => Loc::getMessage('QUOTE_CREATE_ON_BASIS_TITLE_MSGVER_1'),
			'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if ($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_QUOTE_DETAILS'],
				array('quote_id' => $arParams['ELEMENT_ID'])
			),
			array('copy' => 1)
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => Loc::getMessage('QUOTE_COPY'),
			'TITLE' => Loc::getMessage('QUOTE_COPY_TITLE_MSGVER_1'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if ($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => Loc::getMessage('QUOTE_DELETE'),
			'TITLE' => Loc::getMessage('QUOTE_DELETE_TITLE_MSGVER_1'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	$this->IncludeComponentTemplate();

	return;
}

if ($arParams['TYPE'] === 'list')
{
	$arResult['BUTTONS'][] = [
		'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
		'LINK' => \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::Quote)
			->setSection(
				!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
			)
			->setSubSection(
				!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
			)
			->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_BUTTON)
			->buildUri(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'] ?? '',
					[
						'quote_id' => 0
					]
				)
			)
			->getUri()
		,
		'HIGHLIGHT' => true,
		'IS_DISABLED' => !$bAdd,
		'HINT' => Loc::getMessage('CRM_QUOTE_ADD_HINT_MSGVER_1')
	];

	if (!$isInSlider)
	{
		$arResult['BUTTONS'][] = ['NEWBAR' => true];
	}

	if ($bConfig && !$isInSlider)
	{
		\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
		$userFieldListUrl = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getUserFieldListUrl(\CCrmOwnerType::Quote);
		if ($userFieldListUrl)
		{
			$userFieldListUrl = $userFieldListUrl->__toString();
		}
		if ($userFieldListUrl)
		{
			$arResult['BUTTONS'][] = [
				'TEXT' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
				'ONCLICK' => 'BX.SidePanel.Instance.open("' . $userFieldListUrl . '")',
			];
		}
	}

	/*if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => Loc::getMessage('QUOTE_IMPORT'),
			'TITLE' => Loc::getMessage('QUOTE_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}*/

	if ($bExport && !$isInSlider)
	{
		/*if ($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}*/

		$stExportId = 'EXPORT_'.\CCrmOwnerType::QuoteName;
		$componentName = 'bitrix:crm.quote.list';

		$arResult['EXPORT_CSV_PARAMS'] = [
			'id' => $stExportId. '_CSV',
			'controller' => 'bitrix:crm.api.export',
			'queue' => [
				[
					'action' => 'dispatcher',
				],
			],
			'params' => [
				'SITE_ID' => SITE_ID,
				'ENTITY_TYPE' => \CCrmOwnerType::QuoteName,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					$componentName,
					[
						'QUOTE_COUNT' => '20',
						'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'] ?? '',
						'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
						'PATH_TO_QUOTE_KANBAN' => $arResult['PATH_TO_QUOTE_KANBAN'] ?? '',
						'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
						'NAVIGATION_CONTEXT_ID' => \CCrmOwnerType::QuoteName
					]
				),
			],
			'optionsFields' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('QUOTE_STEXPORT_OPTION_EXPORT_ALL_FIELDS_MSGVER_1'),
					'value' => 'N'
				),
				'EXPORT_PRODUCT_FIELDS' => array(
					'name' => 'EXPORT_PRODUCT_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('QUOTE_STEXPORT_OPTION_EXPORT_PRODUCT_FIELDS'),
					'value' => 'N'
				),
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('QUOTE_EXPORT_CSV_TITLE_MSGVER_1'),
				'DialogSummary' => Loc::getMessage('QUOTE_STEXPORT_SUMMARY_MSGVER_1'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('QUOTE_EXPORT_EXCEL_TITLE_MSGVER_1');

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('QUOTE_EXPORT_CSV_TITLE_MSGVER_1'),
			'TEXT' => Loc::getMessage('QUOTE_EXPORT_CSV_MSGVER_1'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('QUOTE_EXPORT_EXCEL_TITLE_MSGVER_1'),
			'TEXT' => Loc::getMessage('QUOTE_EXPORT_EXCEL_MSGVER_1'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		unset($stExportId);
	}

	$isAddDelimiter = true;
	$permissionItem = PermissionItem::createByEntity(CCrmOwnerType::Quote);
	if (isset($arParams['ANALYTICS']) && is_array($arParams['ANALYTICS']))
	{
		$permissionItem->setAnalytics($arParams['ANALYTICS']);
	}
	if ($permissionItem->canShow())
	{
		$isAddDelimiter = false;
		$arResult['BUTTONS'][] = $permissionItem->interfaceToolbarDelimiter();
		$arResult['BUTTONS'][] = $permissionItem->toInterfaceToolbarButton();
	}

	if (count($arResult['BUTTONS']) > 1)
	{
		if ($isAddDelimiter)
		{
			$arResult['BUTTONS'][] = ['SEPARATOR' => true];
		}

		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (
	($arParams['TYPE'] === 'edit' || $arParams['TYPE'] === 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& CCrmQuote::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $CrmPerms)
)
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
			'LOCK_SCRIPT' => $isPermitted ? '' : $restriction->prepareInfoHelperScript()
		),
		'CODE' => 'convert',
		'TEXT' => Loc::getMessage('QUOTE_CREATE_ON_BASIS'),
		'TITLE' => Loc::getMessage('QUOTE_CREATE_ON_BASIS_TITLE_MSGVER_1'),
		'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
	);
}

if ($arParams['TYPE'] === 'show' && !empty($arParams['ELEMENT_ID']))
{
	if (\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'CODE' => 'document',
			'TEXT' => Loc::getMessage('QUOTE_DOCUMENT_BUTTON_TEXT'),
			'TITLE' => Loc::getMessage('QUOTE_DOCUMENT_BUTTON_TITLE_MSGVER_1'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(DocumentGenerator\DataProvider\Quote::class, $arParams['ELEMENT_ID']),
		];
	}

	if (CCrmQuote::isPrintingViaPaymentMethodSupported())
	{
		$menuItems = [];
		$menuItems[] = [
			'text' => Loc::getMessage('QUOTE_PAYMENT_HTML'),
			'title' => Loc::getMessage('QUOTE_PAYMENT_HTML_TITLE_MSGVER_1'),
			'onclick' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: false }])"
		];
		$menuItems[] = [
			'text' => Loc::getMessage('QUOTE_PAYMENT_HTML_BLANK_MSGVER_1'),
			'title' => Loc::getMessage('QUOTE_PAYMENT_HTML_BLANK_TITLE_MSGVER_1'),
			'onclick' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: true }])"
		];

		if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
		{
			$menuItems[] = [
				'text' => Loc::getMessage('QUOTE_PAYMENT_PDF'),
				'title' => Loc::getMessage('QUOTE_PAYMENT_PDF_TITLE_MSGVER_1'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: false }])"
			];
			$menuItems[] = [
				'text' => Loc::getMessage('QUOTE_PAYMENT_PDF_BLANK'),
				'title' => Loc::getMessage('QUOTE_PAYMENT_PDF_BLANK_TITLE_MSGVER_1'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: true }])"
			];
			$menuItems[] = [
				'text' => Loc::getMessage('QUOTE_PAYMENT_EMAIL'),
				'title' => Loc::getMessage('QUOTE_PAYMENT_EMAIL_TITLE_MSGVER_1'),
				'onclick' => "BX.onCustomEvent(window, 'CrmQuoteSendByEmail', [this])"
			];
		}
		if (!empty($menuItems))
		{
			$arResult['BUTTONS'][] = [
				'CODE' => 'leftMenu',
				'TEXT' => Loc::getMessage('QUOTE_LEFT_MENU_TEXT'),
				'TITLE' => Loc::getMessage('QUOTE_LEFT_MENU_TITLE_MSGVER_1'),
				'TYPE' => 'toolbar-menu-left',
				'ITEMS' => $menuItems,
			];
		}
		unset($menuItems);
	}

	if ($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => Loc::getMessage('QUOTE_EDIT'),
			'TITLE' => Loc::getMessage('QUOTE_EDIT_TITLE_MSGVER_1'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
				array(
					'quote_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] === 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => Loc::getMessage('QUOTE_SHOW'),
		'TITLE' => Loc::getMessage('QUOTE_SHOW_TITLE_MSGVER_1'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] === 'edit' || $arParams['TYPE'] === 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => Loc::getMessage('QUOTE_COPY'),
		'TITLE' => Loc::getMessage('QUOTE_COPY_TITLE_MSGVER_1'),
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
		'TEXT' => Loc::getMessage('QUOTE_DELETE_MSGVER_1'),
		'TITLE' => Loc::getMessage('QUOTE_DELETE_TITLE_MSGVER_1'),
		'LINK' => "javascript:quote_delete('".Loc::getMessage('QUOTE_DELETE_DLG_TITLE_MSGVER_1')."', '".Loc::getMessage('QUOTE_DELETE_DLG_MESSAGE_MSGVER_1')."', '".Loc::getMessage('QUOTE_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
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
		'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => 0
			)
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
