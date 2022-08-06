<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CUserTypeManager $USER_FIELD_MANAGER
 * @var \CBitrixComponent $this
 * @var array $arParams
 * @var array $arResult
 */

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$isErrorOccured = false;
$errorMessage = '';

if (!CModule::IncludeModule('crm'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED');
	$isErrorOccured = true;
}

$isBizProcInstalled = IsModuleInstalled('bizproc');
if (!$isErrorOccured && $isBizProcInstalled)
{
	if (!CModule::IncludeModule('bizproc'))
	{
		$errorMessage = GetMessage('BIZPROC_MODULE_NOT_INSTALLED');
		$isErrorOccured = true;
	}
	elseif (!CBPRuntime::isFeatureEnabled())
	{
		$isBizProcInstalled = false;
	}
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccured && !CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
	$isErrorOccured = true;
}

//region Export params
$sExportType = !empty($arParams['EXPORT_TYPE']) ?
	strval($arParams['EXPORT_TYPE']) : (!empty($_REQUEST['type']) ? strval($_REQUEST['type']) : '');
$isInExportMode = false;
$isStExport = false;    // Step-by-step export mode
if (!empty($sExportType))
{
	$sExportType = mb_strtolower(trim($sExportType));
	switch ($sExportType)
	{
		case 'csv':
		case 'excel':
			$isInExportMode = true;
			$isStExport = (isset($arParams['STEXPORT_MODE']) && $arParams['STEXPORT_MODE'] === 'Y');
			break;
		default:
			$sExportType = '';
	}
}

$isStExportAllFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'])
						&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'] === 'Y');
$arResult['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStExport && $isStExportAllFields) ? 'Y' : 'N';

$isStExportRequisiteMultiline = (
	isset($arParams['STEXPORT_INITIAL_OPTIONS']['REQUISITE_MULTILINE'])
	&& $arParams['STEXPORT_INITIAL_OPTIONS']['REQUISITE_MULTILINE'] === 'Y'
);
$arResult['STEXPORT_REQUISITE_MULTILINE'] = ($isStExport && $isStExportRequisiteMultiline) ? 'Y' : 'N';

$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS']) ?
	(int)$arParams['STEXPORT_TOTAL_ITEMS'] : 0;
//endregion

$CCrmContact = new CCrmContact();

if (!$isErrorOccured && $isInExportMode && $CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'EXPORT'))
{
	$errorMessage = \Bitrix\Main\Localization\Loc::getMessage('CRM_PERMISSION_DENIED');
	$isErrorOccured = true;
}

$exportRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getContactExportRestriction();
if (!$isErrorOccured && $isInExportMode && !$exportRestriction->hasPermission())
{
	\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
	$errorMessage = \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR');
	$isErrorOccured = true;
}

if ($isErrorOccured)
{
	if ($isStExport)
	{
		return array('ERROR' => $errorMessage);
	}
	else
	{
		ShowError($errorMessage);
		return;
	}
}

use Bitrix\Crm;
use Bitrix\Crm\Agent\Duplicate\Background\ContactIndexRebuild;
use Bitrix\Crm\Agent\Duplicate\Background\ContactMerge;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$CCrmBizProc = new CCrmBizProc('CONTACT');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();
$enableOutmodedFields = $arResult['ENABLE_OUTMODED_FIELDS'] = ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_DETAILS'] = CrmCheckPath('PATH_TO_CONTACT_DETAILS', $arParams['PATH_TO_CONTACT_DETAILS'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&details');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_CONTACT_MERGE'] = CrmCheckPath('PATH_TO_CONTACT_MERGE', $arParams['PATH_TO_CONTACT_MERGE'], '/contact/merge/');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_DEAL_DETAILS'] = CrmCheckPath('PATH_TO_DEAL_DETAILS', $arParams['PATH_TO_DEAL_DETAILS'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&details');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['PATH_TO_CONTACT_WIDGET'] = CrmCheckPath('PATH_TO_CONTACT_WIDGET', $arParams['PATH_TO_CONTACT_WIDGET'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_PORTRAIT'] = CrmCheckPath('PATH_TO_CONTACT_PORTRAIT', $arParams['PATH_TO_CONTACT_PORTRAIT'], $APPLICATION->GetCurPage());
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;
$arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

$arResult['CATEGORY_ID'] = (int)($arParams['CATEGORY_ID'] ?? 0);

if(LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled())
{
	$arResult['TIME_FORMAT'] = array(
		'tommorow' => 'tommorow',
		's' => 'sago',
		'i' => 'iago',
		'H3' => 'Hago',
		'today' => 'today',
		'yesterday' => 'yesterday',
		//'d7' => 'dago',
		'-' => Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE)
	);
}
else
{
	$arResult['TIME_FORMAT'] = preg_replace('/:s$/', '', Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && $arParams['GADGET_ID'] <> '')
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
if (!empty($arParams['INTERNAL_FILTER']) || $isInGadgetMode)
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	if(empty($arParams['GRID_ID_SUFFIX']))
	{
		$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null? mb_strtoupper($this->GetParent()->GetName()) : '';
	}

	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
	$arSort = $arParams['INTERNAL_SORT'];
}

$enableWidgetFilter = false;
$widgetFilter = null;
if (isset($arParams['WIDGET_DATA_FILTER']) && isset($arParams['WIDGET_DATA_FILTER']['WG']) && $arParams['WIDGET_DATA_FILTER']['WG'] === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $arParams['WIDGET_DATA_FILTER'];
}
elseif (!$bInternal && isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $_REQUEST;
}
if ($enableWidgetFilter)
{
	$dataSourceFilter = null;

	$dataSourceName = isset($widgetFilter['DS']) ? $widgetFilter['DS'] : '';
	if($dataSourceName !== '')
	{
		$dataSource = null;
		try
		{
			$dataSource = Bitrix\Crm\Widget\Data\DataSourceFactory::create(array('name' => $dataSourceName), $userID, true);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}

		try
		{
			$dataSourceFilter = $dataSource ? $dataSource->prepareEntityListFilter($widgetFilter) : null;
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
		catch(Bitrix\Main\InvalidOperationException $e)
		{
		}
	}

	if(is_array($dataSourceFilter) && !empty($dataSourceFilter))
	{
		$arFilter = $dataSourceFilter;
	}
	else
	{
		$enableWidgetFilter = false;
	}
}

$enableCounterFilter = false;
if(!$bInternal && isset($_REQUEST['counter']))
{
	$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::resolveID($_REQUEST['counter']);
	$counter = null;
	if(Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
	{
		try
		{
			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Contact,
				$counterTypeID,
				$userID,
				array_merge(
					Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
					['CATEGORY_ID' => $arResult['CATEGORY_ID']]
				)
			);

			$arFilter = $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmContact::TABLE_ALIAS,
					'MASTER_IDENTITY' => 'ID'
				)
			);
			$enableCounterFilter = !empty($arFilter);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
	}
}

$request = Main\Application::getInstance()->getContext()->getRequest();
$fromAnalytics = $request->getQuery('from_analytics') === 'Y';
$enableReportFilter = false;
if($fromAnalytics)
{
	$reportId = Bitrix\Main\Context::getCurrent()->getRequest()['report_id'];
	if($reportId != '')
	{
		$reportHandler = Crm\Integration\Report\ReportHandlerFactory::createWithReportId($reportId);
		$reportFilter = $reportHandler ? $reportHandler->prepareEntityListFilter(Bitrix\Main\Context::getCurrent()->getRequest()) : null;

		if(is_array($reportFilter) && !empty($reportFilter))
		{
			$arFilter = $reportFilter;
			$enableReportFilter = true;
		}
	}
}

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter || $enableReportFilter);

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID, ['categoryId' => $arResult['CATEGORY_ID']]);
$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = (new Crm\Component\EntityList\GridId(CCrmOwnerType::Contact))
	->getValue((string)$arParams['GRID_ID_SUFFIX'])
;

$arResult['HONORIFIC'] = CCrmStatus::GetStatusListEx('HONORIFIC');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['WEBFORM_LIST'] = WebFormManager::getListNamesEncoded();
$arResult['EXPORT_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = [];
$arResult['FILTER_PRESETS'] = array();

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['EXTERNAL_SALES'] = CCrmExternalSaleHelper::PrepareListItems();
$arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
$arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
if($arResult['CALL_LIST_UPDATE_MODE'])
{
	AddEventHandler('crm', 'onCrmContactListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmContactListItemBuildMenu'));
}

$addressLabels = EntityAddress::getShortLabels();
$requisite = new \Bitrix\Crm\EntityRequisite();

//region Filter Presets Initialization
if (!$bInternal)
{
	$filterFlags = Crm\Filter\ContactSettings::FLAG_NONE;
	if($enableOutmodedFields)
	{
		$filterFlags |= Crm\Filter\ContactSettings::FLAG_ENABLE_ADDRESS;
	}
	$entityFilter = Crm\Filter\Factory::createEntityFilter(
		new Crm\Filter\ContactSettings(['ID' => $arResult['GRID_ID'], 'flags' => $filterFlags])
	);
	$arResult['FILTER_PRESETS'] = (new Bitrix\Crm\Filter\Preset\Contact())
		->setUserId((int) $arResult['CURRENT_USER_ID'])
		->setUserName(CCrmViewHelper::GetFormattedUserName($arResult['CURRENT_USER_ID'], $arParams['NAME_TEMPLATE']))
		->setDefaultValues($entityFilter->getDefaultFieldIDs())
		->getDefaultPresets()
	;
}
//endregion

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Crm\Filter\UiFilterOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);

//region Navigation Params
if ($arParams['CONTACT_COUNT'] <= 0)
{
	$arParams['CONTACT_COUNT'] = 20;
}
$arNavParams = $gridOptions->GetNavParams(array('nPageSize' => $arParams['CONTACT_COUNT']));
$arNavParams['bShowAll'] = false;
if(isset($arNavParams['nPageSize']) && $arNavParams['nPageSize'] > 100)
{
	$arNavParams['nPageSize'] = 100;
}
//endregion

//region Filter initialization
if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = ['TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS'];

	$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
	if(empty($effectiveFilterFieldIDs))
	{
		$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
	}

	//region HACK: Preload fields for filter of user activities & webforms
	if(!in_array('ASSIGNED_BY_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ASSIGNED_BY_ID';
	}

	if(!in_array('ACTIVITY_COUNTER', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_COUNTER';
	}

	if(!in_array('WEBFORM_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'WEBFORM_ID';
	}

	Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);
	//endregion

	foreach($effectiveFilterFieldIDs as $filterFieldID)
	{
		$filterField = $entityFilter->getField($filterFieldID);
		if($filterField)
		{
			$arResult['FILTER'][] = $filterField->toArray();
		}
	}
}
//endregion

//region Headers initialization
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'CONTACT_SUMMARY', 'name' => GetMessage('CRM_COLUMN_CONTACT'), 'sort' => 'full_name', 'width' => 200, 'default' => true, 'editable' => false),
);

// Don't display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array(
		'id' => 'ACTIVITY_ID',
		'name' => GetMessage('CRM_COLUMN_ACTIVITY'),
		'sort' => 'nearest_activity',
		'default' => true,
		'prevent_default' => false
	);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMPANY_INFO'), 'sort' => 'company_title', 'editable' => false),
		array('id' => 'PHOTO', 'name' => GetMessage('CRM_COLUMN_PHOTO'), 'sort' => false, 'editable' => false),
		array(
			'id' => 'HONORIFIC',
			'name' => GetMessage('CRM_COLUMN_HONORIFIC'),
			'sort' => false,
			'type' => 'list',
			'editable' => array(
				'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + CCrmStatus::GetStatusList('HONORIFIC')
			)
		),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'editable' => true, 'class' => 'username'),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME'), 'sort' => 'last_name', 'editable' => true, 'class' => 'username'),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'), 'sort' => 'second_name', 'editable' => true, 'class' => 'username'),
		array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'sort' => 'BIRTHDATE', 'first_order' => 'desc', 'type' => 'date', 'editable' => true),
		array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST'), 'sort' => 'post', 'editable' => true),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_title', 'editable' => false),
		array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE'), 'sort' => 'type_id', 'type' => 'list', 'editable' => array('items' => CCrmStatus::GetStatusList('CONTACT_TYPE'))),
		array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username')
	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS'], ['LINK']);
if($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

Crm\Service\Container::getInstance()->getParentFieldManager()->prepareGridHeaders(
	\CCrmOwnerType::Contact,
	$arResult['HEADERS']
);

if($enableOutmodedFields)
{
	$arResult['HEADERS'] = array_merge(
		$arResult['HEADERS'],
		array(
			array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(), 'sort' => false, 'editable' => false),
			array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address', 'editable' => false),
			array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2', 'editable' => false),
			array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city', 'editable' => false),
			array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region', 'editable' => false),
			array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province', 'editable' => false),
			array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code', 'editable' => false),
			array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country', 'editable' => false)
		)
	);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'editable' => false),
		array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'sort' => 'source_id', 'type' => 'list', 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE'))),
		array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'editable' => false),
		array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_EXPORT_NEW'), 'type' => 'checkbox', 'type' => 'checkbox', 'editable' => true),
		array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'editable' => false, 'class' => 'username'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'first_order' => 'desc', 'default' => true, 'class' => 'date'),
		array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'editable' => false, 'class' => 'username'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'first_order' => 'desc', 'class' => 'date'),
		array('id' => 'WEBFORM_ID', 'name' => GetMessage('CRM_COLUMN_WEBFORM'), 'sort' => 'webform_id', 'type' => 'list')
	)
);

Tracking\UI\Grid::appendColumns($arResult['HEADERS']);

$utmList = \Bitrix\Crm\UtmTable::getCodeNames();
foreach ($utmList as $utmCode => $utmName)
{
	$arResult['HEADERS'][] = array(
		'id' => $utmCode,
		'name' => $utmName,
		'sort' => false, 'default' => $isInExportMode, 'editable' => false
	);
}

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

$arResult['HEADERS_SECTIONS'] = [
	[
		'id' => 'CONTACT',
		'name' => Loc::getMessage('CRM_COLUMN_CONTACT'),
		'default' => true,
		'selected' => true,
	],
];

$arBPData = array();
if ($isBizProcInstalled)
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(['crm', 'CCrmDocumentContact', 'CONTACT'], false);
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentContact', 'CONTACT'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowTemplateId' => $arBP['ID'],
				'UserIsAdmin' => $isAdmin
			)
		))
		{
			continue;
		}
		$arResult['HEADERS'][] = array('id' => 'BIZPROC_'.$arBP['ID'], 'name' => $arBP['NAME'], 'sort' => false, 'editable' => false);
	}

	if ($arBPData)
	{
		CJSCore::Init('bp_starter');
	}
}

// list all filds for export
$exportAllFieldsList = array();
if ($isInExportMode && $isStExportAllFields)
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		$exportAllFieldsList[$arHeader['id']] = true;
	}
}
unset($arHeader);

// requisite entity fields
if (!($isInExportMode && $isStExport && $isStExportRequisiteMultiline))
{
	$requisite->prepareEntityListHeaderFields($arResult['HEADERS']);
}

//endregion Headers initialization

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() overvise the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
$isStepRunningProcess = false;

if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	$actionToken = 'action_token_'.$arResult['GRID_ID'];

	// Adapt data from BX.CrmLongRunningProcessDialog request
	if (
		$arResult['IS_AJAX_CALL'] &&
		isset($_POST['PARAMS']['controls']) &&
		is_array($_POST['PARAMS']['controls']) &&
		isset($_POST['PARAMS']['controls'][$postAction])
	)
	{
		$isStepRunningProcess = true;
		$_POST = $_POST['PARAMS'];
	}

	//We need to check grid 'controls'
	$controls = isset($_POST['controls']) && is_array($_POST['controls']) ? $_POST['controls'] : array();
	if ($actionData['METHOD'] == 'POST' && (isset($controls[$postAction]) || isset($_POST[$postAction])))
	{
		CUtil::JSPostUnescape();

		$actionData['ACTIVE'] = true;

		if(isset($controls[$postAction]))
		{
			$actionData['NAME'] = $controls[$postAction];
		}
		else
		{
			$actionData['NAME'] = $_POST[$postAction];
			unset($_POST[$postAction], $_REQUEST[$postAction]);
		}

		if(isset($controls[$actionToken]))
		{
			$actionData['ACTION_TOKEN'] = $controls[$actionToken];
		}

		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($controls[$allRows]))
		{
			$actionData['ALL_ROWS'] = $controls[$allRows] == 'Y';
		}
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['rows']) && is_array($_POST['rows']))
		{
			$actionData['ID'] = $_POST['rows'];
		}
		elseif(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		if(isset($_POST['ACTION_ASSIGNED_BY_ID']) || isset($controls['ACTION_ASSIGNED_BY_ID']))
		{
			$assignedByID = 0;
			if(isset($_POST['ACTION_ASSIGNED_BY_ID']))
			{
				if(!is_array($_POST['ACTION_ASSIGNED_BY_ID']))
				{
					$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID']);
				}
				elseif(count($_POST['ACTION_ASSIGNED_BY_ID']) > 0)
				{
					$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID'][0]);
				}
				unset($_POST['ACTION_ASSIGNED_BY_ID'], $_REQUEST['ACTION_ASSIGNED_BY_ID']);
			}
			else
			{
				$assignedByID = (int)$controls['ACTION_ASSIGNED_BY_ID'];
			}

			$actionData['ASSIGNED_BY_ID'] = $assignedByID;
		}

		if(isset($_POST['ACTION_EXPORT']) || isset($controls['ACTION_EXPORT']))
		{
			if(isset($_POST['ACTION_EXPORT']))
			{
				$actionData['EXPORT'] = mb_strtoupper($_POST['ACTION_EXPORT']) === 'Y' ? 'Y' : 'N';
				unset($_POST['ACTION_EXPORT'], $_REQUEST['ACTION_EXPORT']);
			}
			else
			{
				$actionData['EXPORT'] = mb_strtoupper($controls['ACTION_EXPORT']) === 'Y' ? 'Y' : 'N';
			}
		}

		if(isset($_POST['ACTION_OPENED']) || isset($controls['ACTION_OPENED']))
		{
			if(isset($_POST['ACTION_OPENED']))
			{
				$actionData['OPENED'] = mb_strtoupper($_POST['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
				unset($_POST['ACTION_OPENED'], $_REQUEST['ACTION_OPENED']);
			}
			else
			{
				$actionData['OPENED'] = mb_strtoupper($controls['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
			}
		}

		$actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
	}
}
//endregion Try to extract user action data

//region Step running process params
if ($isStepRunningProcess)
{
	$stepRunningResultLimit = 500; // items per step
	$stepRunningTimeLimit = 25; // seconds per step
	$stepRunningStartTime = time();

	$maxExecutionTime = (int)ini_get('max_execution_time');
	if ($maxExecutionTime > 0 && $maxExecutionTime < $stepRunningTimeLimit)
	{
		$stepRunningTimeLimit = $maxExecutionTime - 5;
	}

	if (!isset($_SESSION[$arResult['GRID_ID']]))
	{
		$_SESSION[$arResult['GRID_ID']] = array(
			'PROCESSED_ITEMS' => 0,
			'TOTAL_ITEMS' => 0,
			'LAST_ID' => 0,
		);
	}
	$stepRunningParams = &$_SESSION['CRM_PROCESS_'.$arResult['GRID_ID']];

	if (!empty($actionData['ACTION_TOKEN']))
	{
		if (isset($stepRunningParams['ACTION_TOKEN']) && $stepRunningParams['ACTION_TOKEN'] !== $actionData['ACTION_TOKEN'])
		{
			// new process
			$stepRunningParams['PROCESSED_ITEMS'] = 0;
			$stepRunningParams['TOTAL_ITEMS'] = 0;
			$stepRunningParams['LAST_ID'] = 0;
		}
		$stepRunningParams['ACTION_TOKEN'] = $actionData['ACTION_TOKEN'];
	}
}
//endregion

// HACK: for clear filter by CREATED_BY_ID, MODIFY_BY_ID and ASSIGNED_BY_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_REQUEST['CREATED_BY_ID_name']) && $_REQUEST['CREATED_BY_ID_name'] === '')
	{
		$_REQUEST['CREATED_BY_ID'] = $_GET['CREATED_BY_ID'] = array();
	}

	if(isset($_REQUEST['MODIFY_BY_ID_name']) && $_REQUEST['MODIFY_BY_ID_name'] === '')
	{
		$_REQUEST['MODIFY_BY_ID'] = $_GET['MODIFY_BY_ID'] = array();
	}

	if(isset($_REQUEST['ASSIGNED_BY_ID_name']) && $_REQUEST['ASSIGNED_BY_ID_name'] === '')
	{
		$_REQUEST['ASSIGNED_BY_ID'] = $_GET['ASSIGNED_BY_ID'] = array();
	}
}

if(!$arResult['IS_EXTERNAL_FILTER'])
{
	$arFilter += $filterOptions->getFilter($arResult['FILTER']);
}

$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmContact::$sUFEntityID, $arFilter);

$arFilter['@CATEGORY_ID'] = $arResult['CATEGORY_ID'];

//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if(!$searchRestriction->isExceeded(CCrmOwnerType::Contact))
{
	$searchRestriction->notifyIfLimitAlmostExceed(CCrmOwnerType::Contact);

	Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Contact, $arFilter);
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact)
	);
}
//endregion

//region Activity Counter Filter
if(isset($arFilter['ACTIVITY_COUNTER']))
{
	if(is_array($arFilter['ACTIVITY_COUNTER']))
	{
		$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::joinType(
			array_filter($arFilter['ACTIVITY_COUNTER'], 'is_numeric')
		);
	}
	else
	{
		$counterTypeID = (int)$arFilter['ACTIVITY_COUNTER'];
	}

	$counter = null;
	if($counterTypeID > 0)
	{
		$counterUserIDs = array();
		if(isset($arFilter['ASSIGNED_BY_ID']))
		{
			if(is_array($arFilter['ASSIGNED_BY_ID']))
			{
				$counterUserIDs = array_filter($arFilter['ASSIGNED_BY_ID'], 'is_numeric');
			}
			elseif($arFilter['ASSIGNED_BY_ID'] > 0)
			{
				$counterUserIDs[] = $arFilter['ASSIGNED_BY_ID'];
			}
		}

		try
		{
			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Contact,
				$counterTypeID,
				0,
				array_merge(
					Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
					['CATEGORY_ID' => $arResult['CATEGORY_ID']]
				)
			);

			$arFilter += $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmContact::TABLE_ALIAS,
					'MASTER_IDENTITY' => 'ID',
					'USER_IDS' => $counterUserIDs
				)
			);
			unset($arFilter['ASSIGNED_BY_ID']);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
	}
}
//endregion

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);
$requisite->prepareEntityListFilter($arFilter);

$arImmutableFilters = array(
	'FM', 'ID', 'COMPANY_ID', 'COMPANY_ID_value', 'ASSOCIATED_COMPANY_ID', 'ASSOCIATED_DEAL_ID',
	'ASSIGNED_BY_ID', 'ASSIGNED_BY_ID_value',
	'CREATED_BY_ID', 'CREATED_BY_ID_value',
	'MODIFY_BY_ID', 'MODIFY_BY_ID_value',
	'TYPE_ID', 'SOURCE_ID', 'WEBFORM_ID', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'HAS_PHONE', 'HAS_EMAIL', 'RQ',
	'SEARCH_CONTENT',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID',
	'@CATEGORY_ID',
);

foreach ($arFilter as $k => $v)
{
	//Check if first key character is aplpha and key is not immutable
	if(preg_match('/^[a-zA-Z]/', $k) !== 1 || in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	if (Crm\Service\ParentFieldManager::isParentFieldName($k))
	{
		$arFilter[$k] = Crm\Service\ParentFieldManager::transformEncodedFilterValueIntoInteger($k, $v);
		continue;
	}

	$arMatch = array();
	if($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}
	elseif($k === 'ADDRESS'
		|| $k === 'ADDRESS_2'
		|| $k === 'ADDRESS_CITY'
		|| $k === 'ADDRESS_REGION'
		|| $k === 'ADDRESS_PROVINCE'
		|| $k === 'ADDRESS_POSTAL_CODE'
		|| $k === 'ADDRESS_COUNTRY')
	{
		$v = trim($v);
		if($v === '')
		{
			continue;
		}

		if(!isset($arFilter['ADDRESSES']))
		{
			$arFilter['ADDRESSES'] = array();
		}

		$addressTypeID = ContactAddress::resolveEntityFieldTypeID($k);
		if(!isset($arFilter['ADDRESSES'][$addressTypeID]))
		{
			$arFilter['ADDRESSES'][$addressTypeID] = array();
		}

		$n = ContactAddress::mapEntityField($k, $addressTypeID);
		$arFilter['ADDRESSES'][$addressTypeID][$n] = "{$v}%";
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		\Bitrix\Crm\UI\Filter\Range::prepareFrom($arFilter, $arMatch[1], $v);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if ($v != '' && ($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
		{
			$v = CCrmDateTimeHelper::SetMaxDayTime($v);
		}
		\Bitrix\Crm\UI\Filter\Range::prepareTo($arFilter, $arMatch[1], $v);
	}
	elseif($k === 'COMPANY_TITLE')
	{
		//Rename field for support of multiple company bindings. See \CCrmContact::__AfterPrepareSql
		$arFilter['ASSOCIATED_COMPANY_TITLE'] = $arFilter['COMPANY_TITLE'];
		unset($arFilter['COMPANY_TITLE']);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']) && $v !== false)
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif($k === 'COMMUNICATION_TYPE')
	{
		if(!is_array($v))
		{
			$v = array($v);
		}
		foreach($v as $commTypeID)
		{
			if($commTypeID === CCrmFieldMulti::PHONE)
			{
				$arFilter['=HAS_PHONE'] = 'Y';
			}
			elseif($commTypeID === CCrmFieldMulti::EMAIL)
			{
				$arFilter['=HAS_EMAIL'] = 'Y';
			}
		}
		unset($arFilter['COMMUNICATION_TYPE']);
	}
	elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && mb_strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1 && $v !== false)
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

//region POST & GET actions processing
if($actionData['ACTIVE'])
{
	if ($actionData['METHOD'] == 'POST')
	{
		if($actionData['NAME'] == 'delete')
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDel += $arFilter;
				}

				$obRes = CCrmContact::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arContact = $obRes->Fetch())
				{
					$ID = $arContact['ID'];
					$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', array($ID));
					if (!$userPermissions->CheckEnityAccess('CONTACT', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr)
						&& $CCrmContact->Delete($ID, array('PROCESS_BIZPROC' => false)))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', array($ID));
					if (!$userPermissions->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && (is_array($arHead['editable']) || $arHead['editable'] === true) && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}

					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();
						if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_REQUIRED_USER_FIELD_CHECK' => true)))
						{
							$DB->Commit();

							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Contact,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						else
						{
							$arResult['ERRORS'][] = [
								'TITLE' => Main\Text\HtmlFilter::encode($arUpdateData['TITLE'] ?? $ID),
								'TEXT' => Main\Text\HtmlFilter::encode(strip_tags($CCrmContact->LAST_ERROR)),
							];
							$DB->Rollback();
						}
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'tasks')
		{
			if (isset($actionData['ID']) && is_array($actionData['ID']))
			{
				$arTaskID = array();
				foreach($actionData['ID'] as $ID)
				{
					$arTaskID[] = 'C_'.$ID;
				}

				$APPLICATION->RestartBuffer();

				$taskUrl = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
						array(
							'task_id' => 0,
							'user_id' => $userID
						)
					),
					array(
						'UF_CRM_TASK' => implode(';', $arTaskID),
						'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
						'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
						'back_url' => urlencode($arParams['PATH_TO_CONTACT_LIST'])
					)
				);
				if ($actionData['AJAX_CALL'])
				{
					echo '<script> parent.window.location = "'.CUtil::JSEscape($taskUrl).'";</script>';
					exit();
				}
				else
				{
					LocalRedirect($taskUrl);
				}
			}
		}
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmContact::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arContact = $dbRes->Fetch())
					{
						$arIDs[] = $arContact['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', $arIDs);


				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Contact,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'export')
		{
			if(isset($actionData['EXPORT']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					//$arActionFilter['!EXPORT'] = $actionData['EXPORT'];

					$arActionNavParams = false;
					$arActionOrder = array();
					if ($isStepRunningProcess)
					{
						$arActionOrder['ID'] = 'ASC';
						// set limitation
						if (isset($stepRunningParams['TOTAL_ITEMS']) && $stepRunningParams['TOTAL_ITEMS'] > 0)
						{
							$arActionNavParams = array('nTopCount' => $stepRunningResultLimit);
						}
						if (isset($stepRunningParams['LAST_ID']) && $stepRunningParams['LAST_ID'] > 0)
						{
							$arActionFilter['>ID'] = $stepRunningParams['LAST_ID'];
						}
					}

					$dbRes = \CCrmContact::GetListEx(
						$arActionOrder,
						$arActionFilter,
						false,
						$arActionNavParams,
						array('ID','EXPORT')
					);
					if ($isStepRunningProcess && empty($stepRunningParams['TOTAL_ITEMS']))
					{
						$stepRunningParams['TOTAL_ITEMS'] = $dbRes->SelectedRowsCount();
						$stepRunningParams['PROCESSED_ITEMS'] = 0;
						$stepRunningParams['LAST_ID'] = 0;
					}
					while($arContact = $dbRes->Fetch())
					{
						if ($arContact['ID'] == $actionData['EXPORT'])
						{
							continue;
						}
						$arIDs[] = $arContact['ID'];

						// item count limit
						if ($isStepRunningProcess && count($arIDs) >= $stepRunningResultLimit)
						{
							break;
						}
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', $arIDs);


				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'EXPORT' => $actionData['EXPORT']
					);

					if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Contact,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}

					if ($isStepRunningProcess)
					{
						$stepRunningParams['PROCESSED_ITEMS'] ++;
						$stepRunningParams['LAST_ID'] = $ID;

						// time limit per step
						if ((time() - $stepRunningStartTime) >= $stepRunningTimeLimit)
						{
							break;
						}
					}
				}

				if ($isStepRunningProcess)
				{
					$result = array(
						'STATUS' => ($stepRunningParams['PROCESSED_ITEMS'] >= $stepRunningParams['TOTAL_ITEMS'] ? 'COMPLETED' : 'PROGRESS'),
						'TOTAL_ITEMS' => $stepRunningParams['TOTAL_ITEMS'],
						'PROCESSED_ITEMS' => $stepRunningParams['PROCESSED_ITEMS'],
					);
					if ($stepRunningParams['PROCESSED_ITEMS'] >= $stepRunningParams['TOTAL_ITEMS'])
					{
						unset($stepRunningParams, $_SESSION['CRM_PROCESS_'.$arResult['GRID_ID']]);
					}

					$APPLICATION->RestartBuffer();
					Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
					echo \Bitrix\Main\Web\Json::encode($result);

					require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
					die();
				}
			}
		}
		elseif ($actionData['NAME'] == 'mark_as_opened')
		{
			if(isset($actionData['OPENED']) && $actionData['OPENED'] != '')
			{
				$isOpened = mb_strtoupper($actionData['OPENED']) === 'Y' ? 'Y' : 'N';
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmContact::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arContact = $dbRes->Fetch())
					{
						if(isset($arContact['OPENED']) && $arContact['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arContact['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmContact::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arContact = $dbRes->Fetch())
					{
						if(isset($arContact['OPENED']) && $arContact['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arContact['ID'];
					}
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();
					$arUpdateData = array('OPENED' => $isOpened);
					if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Contact,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_CONTACT_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);
			$arEntityAttr = $userPermissions->GetEntityAttr('CONTACT', array($ID));
			if(CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::ContactName, $ID, $userPermissions, $arEntityAttr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $arEntityAttr)
					&& $CCrmContact->Delete($ID, array('PROCESS_BIZPROC' => false)))
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_contact' : $arParams['PATH_TO_CONTACT_LIST']);
		}
	}
}
//endregion POST & GET actions processing

$_arSort = $gridOptions->GetSorting(array(
	'sort' => array('full_name' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

if ($isInExportMode)
{
	$arFilter['EXPORT'] = 'Y';
}

$arSelect = $gridOptions->GetVisibleColumns();

// Remove column for deleted RQ & UF
if ($requisite->normalizeEntityListFields($arSelect, $arResult['HEADERS'])
	|| $CCrmUserType->NormalizeFields($arSelect))
{
	$gridOptions->SetVisibleColumns($arSelect);
}

$rqSelect = $requisite->separateEntityListRqFields($arSelect);

$arSelectMap = array_fill_keys($arSelect, true);

$arResult['ENABLE_BIZPROC'] = $arResult['IS_BIZPROC_AVAILABLE'] = $isBizProcInstalled;
$arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');

if($arResult['ENABLE_TASK'])
{
	$arResult['TASK_CREATE_URL'] = CHTTP::urlAddParams(
		CComponentEngine::MakePathFromTemplate(
			COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
			array(
				'task_id' => 0,
				'user_id' => $userID
			)
		),
		array(
			'UF_CRM_TASK' => '#ENTITY_KEYS#',
			'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
			'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
			'back_url' => urlencode($arParams['PATH_TO_CONTACT_LIST'])
		)
	);
}

// Export all fields
if ($isInExportMode && $isStExport && $isStExportAllFields)
{
	$arSelectMap = $exportAllFieldsList;
	$requisite->normalizeEntityListFields($arSelectMap, $arResult['HEADERS']);
}

// Fill in default values if empty
if (empty($arSelectMap))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelectMap[$arHeader['id']] = true;
		}
	}

	//Disable bizproc fields processing
	$arResult['ENABLE_BIZPROC'] = false;
}
else
{
	if($arResult['ENABLE_BIZPROC'])
	{
		//Check if bizproc fields selected
		$hasBizprocFields = false;
		foreach($arSelectMap as $k => $v)
		{
			if(strncmp($k, 'BIZPROC_', 8) === 0)
			{
				$hasBizprocFields = true;
				break;
			}
		}
		$arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
	}
	unset($fieldName);
}

// separate entity requisite fields
if ($isInExportMode && $isStExport && $isStExportRequisiteMultiline)
{
	$arSelectedHeaders = array_keys($arSelectMap);
}
else
{
	$arSelectedHeaders = array_merge(array_keys($arSelectMap), $rqSelect);
}

if ($isInGadgetMode)
{
	$arSelectMap['DATE_CREATE'] =
		$arSelectMap['HONORIFIC'] =
		$arSelectMap['NAME'] =
		$arSelectMap['SECOND_NAME'] =
		$arSelectMap['LAST_NAME'] =
		$arSelectMap['LOGIN'] =
		$arSelectMap['TYPE_ID'] = true;
}
else
{
	if(isset($arSelectMap['CONTACT_SUMMARY']))
	{
		$arSelectMap['PHOTO'] =
		$arSelectMap['HONORIFIC'] =
		$arSelectMap['NAME'] =
		$arSelectMap['LAST_NAME'] =
		$arSelectMap['SECOND_NAME'] =
		$arSelectMap['TYPE_ID'] = true;
	}

	if($arSelectMap['ASSIGNED_BY'])
	{
		$arSelectMap['ASSIGNED_BY_LOGIN'] =
			$arSelectMap['ASSIGNED_BY_NAME'] =
			$arSelectMap['ASSIGNED_BY_LAST_NAME'] =
			$arSelectMap['ASSIGNED_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['COMPANY_ID']))
	{
		$arSelectMap['COMPANY_TITLE'] =
		$arSelectMap['POST'] = true;
	}
	else
	{
		// Required for construction of URLs
		$arSelectMap['COMPANY_ID'] = true;
	}

	if(isset($arSelectMap['CONTACT_COMPANY']))
	{
		$arSelectMap['COMPANY_TITLE'] =
			$arSelectMap['POST'] = true;
	}

	if(isset($arSelectMap['ACTIVITY_ID']))
	{
		$arSelectMap['ACTIVITY_TIME'] =
			$arSelectMap['ACTIVITY_SUBJECT'] =
			$arSelectMap['C_ACTIVITY_ID'] =
			$arSelectMap['C_ACTIVITY_TIME'] =
			$arSelectMap['C_ACTIVITY_SUBJECT'] =
			$arSelectMap['C_ACTIVITY_RESP_ID'] =
			$arSelectMap['C_ACTIVITY_RESP_LOGIN'] =
			$arSelectMap['C_ACTIVITY_RESP_NAME'] =
			$arSelectMap['C_ACTIVITY_RESP_LAST_NAME'] =
			$arSelectMap['C_ACTIVITY_RESP_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['CREATED_BY']))
	{
		$arSelectMap['CREATED_BY_LOGIN'] =
			$arSelectMap['CREATED_BY_NAME'] =
			$arSelectMap['CREATED_BY_LAST_NAME'] =
			$arSelectMap['CREATED_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['MODIFY_BY']))
	{
		$arSelectMap['MODIFY_BY_LOGIN'] =
			$arSelectMap['MODIFY_BY_NAME'] =
			$arSelectMap['MODIFY_BY_LAST_NAME'] =
			$arSelectMap['MODIFY_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		$arSelectMap['ADDRESS'] =
			$arSelectMap['ADDRESS_2'] =
			$arSelectMap['ADDRESS_CITY'] =
			$arSelectMap['ADDRESS_POSTAL_CODE'] =
			$arSelectMap['ADDRESS_POSTAL_CODE'] =
			$arSelectMap['ADDRESS_REGION'] =
			$arSelectMap['ADDRESS_PROVINCE'] =
			$arSelectMap['ADDRESS_COUNTRY'] = true;
	}

	// ID must present in select
	if(!isset($arSelectMap['ID']))
	{
		$arSelectMap['ID'] = true;
	}
}

if ($isInExportMode)
{
	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'CONTACT_SUMMARY' => array(
				'HONORIFIC',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'PHOTO',
				'TYPE_ID'
			),
			'CONTACT_COMPANY' => array(
				'COMPANY_ID',
				'POST'
			),
			'ACTIVITY_ID' => array()
		)
	);

	if(!in_array('ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'ID';
	}

	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
}

$nTopCount = false;
if ($isInGadgetMode)
{
	$nTopCount = $arParams['CONTACT_COUNT'];
}

if($nTopCount > 0 && !isset($arFilter['ID']))
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
{
	$arFilter['PERMISSION'] = 'EXPORT';
}

// HACK: Make custom sort for ASSIGNED_BY and FULL_NAME field
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled())
	{
		$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
		$arSort['assigned_by_name'] = $arSort['assigned_by'];
	}
	else
	{
		$arSort['assigned_by_id'] = $arSort['assigned_by'];
	}
	unset($arSort['assigned_by']);
}
if(isset($arSort['full_name']))
{
	$arSort['last_name'] = $arSort['full_name'];
	$arSort['name'] = $arSort['full_name'];
	unset($arSort['full_name']);
}

if($arSort['date_create'])
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

if(!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSelectMap['ACTIVITY_ID']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}

if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

$arResult['CONTACT'] = array();
$arResult['CONTACT_ID'] = array();
$arResult['CONTACT_UF'] = array();

//region Navigation data initialization
$pageNum = 0;
if ($isInExportMode && $isStExport)
{
	$pageSize = !empty($arParams['STEXPORT_PAGE_SIZE']) ? $arParams['STEXPORT_PAGE_SIZE'] : $arParams['CONTACT_COUNT'];
}
else
{
	$pageSize = !$isInExportMode
		? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['CONTACT_COUNT']) : 0;
}

$enableNextPage = false;
if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
{
	$pageNum = 1;
}
elseif($pageSize > 0 && (isset($arParams['PAGE_NUMBER']) || isset($_REQUEST['page'])))
{
	$pageNum = (int)(isset($arParams['PAGE_NUMBER']) ? $arParams['PAGE_NUMBER'] : $_REQUEST['page']);
	if($pageNum < 0)
	{
		//Backward mode
		$offset = -($pageNum + 1);
		$total = CCrmContact::GetListEx(array(), $arFilter, array());
		$pageNum = (int)(ceil($total / $pageSize)) - $offset;
		if($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}

if (!($isInExportMode && $isStExport))
{
	if ($pageNum > 0)
	{
		if (!isset($_SESSION['CRM_PAGINATION_DATA']))
		{
			$_SESSION['CRM_PAGINATION_DATA'] = array();
		}
		$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
	}
	else
	{
		if (!$bInternal
			&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
			&& isset($_SESSION['CRM_PAGINATION_DATA'])
			&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']])
		)
		{
			$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']];
			if (isset($paginationData['PAGE_NUM'])
				&& isset($paginationData['PAGE_SIZE'])
				&& $paginationData['PAGE_SIZE'] == $pageSize
			)
			{
				$pageNum = (int)$paginationData['PAGE_NUM'];
			}
		}

		if ($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}
//endregion

if ($isInExportMode && $isStExport && $pageNum === 1)
{
	$total = CCrmContact::GetListEx(array(), $arFilter, array());
	if (is_numeric($total))
	{
		$arResult['STEXPORT_TOTAL_ITEMS'] = (int)$total;
	}
}

$limit = $pageSize + 1;
if ($isInExportMode && $isStExport)
{
	$total = (int)$arResult['STEXPORT_TOTAL_ITEMS'];
	$processed = ($pageNum - 1) * $pageSize;
	if ($total - $processed <= $pageSize)
		$limit = $total - $processed;
	unset($total, $processed);
}

if(isset($arSort['nearest_activity']))
{
	$navListOptions = ($isInExportMode && !$isStExport)
		? array()
		: array_merge(
			$arOptions,
			array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
		);

	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Contact,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		false,
		$navListOptions
	);

	$qty = 0;
	while($arContact = $navDbResult->Fetch())
	{
		if($pageSize > 0 && ++$qty > $pageSize)
		{
			$enableNextPage = true;
			break;
		}

		$arResult['CONTACT'][$arContact['ID']] = $arContact;
		$arResult['CONTACT_ID'][$arContact['ID']] = $arContact['ID'];
		$arResult['CONTACT_UF'][$arContact['ID']] = array();
	}

	//region Navigation data storing
	$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

	$arResult['DB_FILTER'] = $arFilter;

	if(!isset($_SESSION['CRM_GRID_DATA']))
	{
		$_SESSION['CRM_GRID_DATA'] = array();
	}
	$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
	//endregion

	$entityIDs = array_keys($arResult['CONTACT']);
	if(!empty($entityIDs))
	{
		if ($isInExportMode && $isStExport)
		{
			if (!is_array($arSort))
			{
				$arSort = array();
			}

			if (!isset($arSort['ID']))
			{
				$order = mb_strtoupper($arSort['nearest_activity']);
				if ($order === 'ASC' || $order === 'DESC')
				{
					$arSort['ID'] = $arSort['nearest_activity'];
				}
				else
				{
					$arSort['ID'] = 'asc';
				}
				unset($order);
			}
		}
		//Permissions are already checked.
		$dbResult = CCrmContact::GetListEx(
			$arSort,
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			$arSelect,
			$arOptions
		);
		while($arContact = $dbResult->GetNext())
		{
			$arResult['CONTACT'][$arContact['ID']] = $arContact;
		}
	}
}
else
{
	$addressSort = array();
	foreach($arSort as $k => $v)
	{
		if(strncmp($k, 'address', 7) === 0)
		{
			$addressSort[mb_strtoupper($k)] = $v;
		}
	}

	if(!empty($addressSort))
	{
		$navListOptions = ($isInExportMode && !$isStExport)
			? array()
			: array_merge(
				$arOptions,
				array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
			);

		$navDbResult = \Bitrix\Crm\ContactAddress::getEntityList(
			EntityAddressType::Primary,
			$addressSort,
			$arFilter,
			false,
			$navListOptions
		);

		$qty = 0;
		while($arContact = $navDbResult->Fetch())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['CONTACT'][$arContact['ID']] = $arContact;
			$arResult['CONTACT_ID'][$arContact['ID']] = $arContact['ID'];
			$arResult['CONTACT_UF'][$arContact['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
		$arResult['DB_FILTER'] = $arFilter;
		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
		//endregion

		$entityIDs = array_keys($arResult['CONTACT']);
		if(!empty($entityIDs))
		{
			$arSort['ID'] = array_shift(array_slice($addressSort, 0, 1));
			//Permissions are already checked.
			$dbResult = CCrmContact::GetListEx(
				$arSort,
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				$arSelect,
				$arOptions
			);
			while($arContact = $dbResult->GetNext())
			{
				$arResult['CONTACT'][$arContact['ID']] = $arContact;
			}
		}
	}
	else
	{
		if ($isInGadgetMode && isset($arNavParams['nTopCount']))
		{
			$navListOptions = array_merge($arOptions, array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount'])));
		}
		else
		{
			$navListOptions = ($isInExportMode && !$isStExport)
				? array()
				: array_merge(
					$arOptions,
					array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
				);
		}

		if ($isInExportMode && $isStExport)
		{
			if (!is_array($arSort))
			{
				$arSort = array();
			}

			if (!isset($arSort['ID']))
			{
				if (!empty($arSort))
				{
					$arSort['ID'] = array_shift(array_slice($arSort, 0, 1));
				}
				else
				{
					$arSort['ID'] = 'asc';
				}
			}
		}

		$dbResult = CCrmContact::GetListEx(
			$arSort,
			$arFilter,
			false,
			false,
			$arSelect,
			$navListOptions
		);

		$qty = 0;
		while($arContact = $dbResult->GetNext())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['CONTACT'][$arContact['ID']] = $arContact;
			$arResult['CONTACT_ID'][$arContact['ID']] = $arContact['ID'];
			$arResult['CONTACT_UF'][$arContact['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

		$arResult['DB_FILTER'] = $arFilter;

		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
		//endregion
	}
}

$arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1 ? 'Y' : 'N';
$arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

$arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
$arResult['PERMS']['ADD']    = !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE');

$arResult['PERM_DEAL'] = CCrmDeal::CheckCreatePermission($userPermissions);
$bQuote = !$CCrmContact->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_QUOTE'] = $bQuote;
$bInvoice = !$userPermissions->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_INVOICE'] = $bInvoice;

$enableExportEvent = $isInExportMode && HistorySettings::getCurrent()->isExportEventEnabled();

$now = time() + CTimeZone::GetOffset();

$allDocumentStates = [];
if ($arResult['ENABLE_BIZPROC'] && !empty($arResult['CONTACT']))
{
	$entityIds = array_map(function ($item)
		{
			return "CONTACT_{$item['ID']}";
		},
		$arResult['CONTACT']);

	$documentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentContact', 'CONTACT'),
		array('crm', 'CCrmDocumentContact', $entityIds)
	);
	foreach ($documentStates as $stateId => $documentState)
	{
		$allDocumentStates[$documentState['DOCUMENT_ID'][2]][$stateId] = $documentState;
	}
}

$parentFieldValues = Crm\Service\Container::getInstance()->getParentFieldManager()->loadParentElementsByChildren(
	\CCrmOwnerType::Contact,
	$arResult['CONTACT']
);

foreach($arResult['CONTACT'] as &$arContact)
{
	$entityID = $arContact['ID'];
	if($enableExportEvent)
	{
		CCrmEvent::RegisterExportEvent(CCrmOwnerType::Contact, $entityID, $userID);
	}

	if (!empty($arContact['PHOTO']))
	{
		if ($isInExportMode)
		{
			if ($arFile = CFile::GetFileArray($arContact['PHOTO']))
				$arContact['PHOTO'] = CHTTP::URN2URI($arFile["SRC"]);
		}
		else
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arContact['PHOTO'],
				array('width' => 100, 'height' => 100),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$arContact['PHOTO'] = CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}
	}

	$companyID = isset($arContact['~COMPANY_ID']) ? (int)$arContact['~COMPANY_ID'] : 0;
	$arContact['PATH_TO_COMPANY_SHOW'] = $companyID > 0
		? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID))
		: '';

	if($companyID > 0)
	{
		$arContact['COMPANY_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $companyID
		);

		if(!CCrmCompany::CheckReadPermission($companyID, $userPermissions))
		{
			$arContact['COMPANY_INFO']['IS_HIDDEN'] = true;
		}
		else
		{
			$arContact['COMPANY_INFO'] =
				array_merge(
					$arContact['COMPANY_INFO'],
					array(
						'TITLE' => isset($arContact['~COMPANY_TITLE']) ? $arContact['~COMPANY_TITLE'] : ('['.$companyID.']'),
						'PREFIX' => "CONTACT_{$arContact['~ID']}",
						'DESCRIPTION' => isset($arContact['~POST']) ? $arContact['~POST'] : ''
					)
				);
		}
	}


	$arContact['PATH_TO_CONTACT_DETAILS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_CONTACT_DETAILS'],
		array('contact_id' => $entityID)
	);

	if($arResult['ENABLE_SLIDER'])
	{
		$arContact['PATH_TO_CONTACT_SHOW'] = $arContact['PATH_TO_CONTACT_DETAILS'];
		$arContact['PATH_TO_CONTACT_EDIT'] = CCrmUrlUtil::AddUrlParams(
			$arContact['PATH_TO_CONTACT_DETAILS'],
			array('init_mode' => 'edit')
		);
	}
	else
	{
		$arContact['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CONTACT_SHOW'],
			array('contact_id' => $entityID)
		);

		$arContact['PATH_TO_CONTACT_EDIT'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CONTACT_EDIT'],
			array('contact_id' => $entityID)
		);
	}

	if ($arResult['PERM_DEAL'])
	{
		$arContact['PATH_TO_DEAL_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arResult['ENABLE_SLIDER'] ? $arParams['PATH_TO_DEAL_DETAILS'] : $arParams['PATH_TO_DEAL_EDIT'],
				array('deal_id' => 0)
			),
			array('contact_id' => $entityID, 'company_id' => $arContact['COMPANY_ID'])
		);
	}

	$arContact['PATH_TO_CONTACT_COPY'] =  CHTTP::urlAddParams(
		$arContact['PATH_TO_CONTACT_EDIT'],
		array('copy' => 1)
	);

	$arContact['PATH_TO_CONTACT_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_CONTACT_LIST'],
		array(
			'action_'.$arResult['GRID_ID'] => 'delete',
			'ID' => $entityID,
			'sessid' => $arResult['SESSION_ID']
		)
	);
	$arContact['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arContact['ASSIGNED_BY'])
	);
	$arContact['~CONTACT_FORMATTED_NAME'] = CCrmContact::PrepareFormattedName(
		array(
			'HONORIFIC' => isset($arContact['~HONORIFIC']) ? $arContact['~HONORIFIC'] : '',
			'NAME' => isset($arContact['~NAME']) ? $arContact['~NAME'] : '',
			'LAST_NAME' => isset($arContact['~LAST_NAME']) ? $arContact['~LAST_NAME'] : '',
			'SECOND_NAME' => isset($arContact['~SECOND_NAME']) ? $arContact['~SECOND_NAME'] : ''
		)
	);
	$arContact['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arContact['~CONTACT_FORMATTED_NAME']);

	$typeID = isset($arContact['TYPE_ID']) ? $arContact['TYPE_ID'] : '';
	$arContact['CONTACT_TYPE_NAME'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;

	$arContact['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arContact['CREATED_BY'])
	);

	$arContact['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arContact['MODIFY_BY'])
	);

	$arContact['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arContact['CREATED_BY_LOGIN'],
			'NAME' => $arContact['CREATED_BY_NAME'],
			'LAST_NAME' => $arContact['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arContact['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arContact['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arContact['MODIFY_BY_LOGIN'],
			'NAME' => $arContact['MODIFY_BY_NAME'],
			'LAST_NAME' => $arContact['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arContact['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	if(isset($arContact['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arContact['~ACTIVITY_TIME']);
		$arContact['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arContact['~ACTIVITY_IS_CURRENT_DAY'] = $arContact['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arContact['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => "C_{$entityID}",
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX').' '),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_CONTACT_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arContact['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'],
					array('quote_id' => 0)
				),
				array('contact_id' => $entityID)
			);
		$arContact['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'],
					array('invoice_id' => 0)
				),
				array('contact' => $entityID)
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arContact['BIZPROC_STATUS'] = '';
		$arContact['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = is_array($allDocumentStates["CONTACT_{$entityID}"]) ?
			$allDocumentStates["CONTACT_{$entityID}"] : [];

		$arContact['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_SHOW'],
				array('contact_id' => $entityID)
			),
			array('CRM_CONTACT_SHOW_V12_active_tab' => 'tab_bizproc')
		);

		$totalTaskQty = 0;
		$docStatesQty = count($arDocumentStates);
		if($docStatesQty === 1)
		{
			$arDocState = $arDocumentStates[array_shift(array_keys($arDocumentStates))];

			$docTemplateID = $arDocState['TEMPLATE_ID'];
			$paramName = "BIZPROC_{$docTemplateID}";
			$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';
			$docName = isset($arDocState['STATE_NAME']) ? $arDocState['STATE_NAME'] : '';
			$docTemplateName = isset($arDocState['TEMPLATE_NAME']) ? $arDocState['TEMPLATE_NAME'] : '';

			if($isInExportMode)
			{
				$arContact[$paramName] = $docTtl;
			}
			else
			{
				$arContact[$paramName] = '<a href="'.htmlspecialcharsbx($arContact['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arContact['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arContact['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
						htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
						': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arContact['PATH_TO_BIZPROC_LIST'].'\'>'.
						htmlspecialcharsbx($docTtl !== '' ? $docTtl : $docName).'</a></span></div>';
			}
		}
		elseif($docStatesQty > 1)
		{
			foreach ($arDocumentStates as &$arDocState)
			{
				$docTemplateID = $arDocState['TEMPLATE_ID'];
				$paramName = "BIZPROC_{$docTemplateID}";
				$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';

				if($isInExportMode)
				{
					$arContact[$paramName] = $docTtl;
				}
				else
				{
					$arContact[$paramName] = '<a href="'.htmlspecialcharsbx($arContact['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arContact['BIZPROC_STATUS'] !== 'attention')
					{
						$arContact['BIZPROC_STATUS'] = 'attention';
					}

					$totalTaskQty += $taskQty;
					if($totalTaskQty > 5)
					{
						break;
					}
				}
			}
			unset($arDocState);

			if(!$isInExportMode)
			{
				$arContact['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arContact['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arContact['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	$arContact['ASSIGNED_BY_ID'] = $arContact['~ASSIGNED_BY_ID'] = isset($arContact['~ASSIGNED_BY']) ? (int)$arContact['~ASSIGNED_BY'] : 0;
	$arContact['~ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => isset($arContact['~ASSIGNED_BY_LOGIN']) ? $arContact['~ASSIGNED_BY_LOGIN'] : '',
			'NAME' => isset($arContact['~ASSIGNED_BY_NAME']) ? $arContact['~ASSIGNED_BY_NAME'] : '',
			'LAST_NAME' => isset($arContact['~ASSIGNED_BY_LAST_NAME']) ? $arContact['~ASSIGNED_BY_LAST_NAME'] : '',
			'SECOND_NAME' => isset($arContact['~ASSIGNED_BY_SECOND_NAME']) ? $arContact['~ASSIGNED_BY_SECOND_NAME'] : ''
		),
		true, false
	);
	$arContact['ASSIGNED_BY'] = htmlspecialcharsbx($arContact['~ASSIGNED_BY']);

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		if ($sExportType === 'csv')
		{
			$arContact['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatTextComma(
				ContactAddress::mapEntityFields($arContact)
			);
		}
		else
		{
			$arContact['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
				ContactAddress::mapEntityFields($arContact)
			);
		}
	}

	if (isset($parentFieldValues[$arContact['ID']]))
	{
		foreach ($parentFieldValues[$arContact['ID']] as $parentEntityTypeId => $parentEntity)
		{
			if ($isInExportMode)
			{
				$arContact[$parentEntity['code']] = $parentEntity['title'];
			}
			else
			{
				$arContact[$parentEntity['code']] = $parentEntity['value'];
			}
		}
	}

	$arResult['CONTACT'][$entityID] = $arContact;
	$arResult['CONTACT_UF'][$entityID] = array();
	$arResult['CONTACT_ID'][$entityID] = $entityID;
}
unset($arContact);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['CONTACT'],
	$arResult['CONTACT_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;
if($arResult['ENABLE_TOOLBAR'])
{
	$arResult['PATH_TO_CONTACT_ADD'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_CONTACT_EDIT'],
		array('contact_id' => 0)
	);

	$addParams = array();

	if($bInternal && isset($arParams['INTERNAL_CONTEXT']) && is_array($arParams['INTERNAL_CONTEXT']))
	{
		$internalContext = $arParams['INTERNAL_CONTEXT'];
		if(isset($internalContext['COMPANY_ID']))
		{
			$addParams['company_id'] = $internalContext['COMPANY_ID'];
		}
	}
	else
	{
		$parentEntityTypeId = (int)($arParams['PARENT_ENTITY_TYPE_ID'] ?? 0);
		$parentEntityId = (int)($arParams['PARENT_ENTITY_ID'] ?? 0);
		if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
		{
			$arResult['PATH_TO_CONTACT_ADD'] = Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
				\CCrmOwnerType::Contact,
				0,
				null,
				new Crm\ItemIdentifier($parentEntityTypeId, $parentEntityId)
			);
		}
	}

	if(!empty($addParams))
	{
		$arResult['PATH_TO_CONTACT_ADD'] = CHTTP::urlAddParams(
			$arResult['PATH_TO_CONTACT_ADD'],
			$addParams
		);
	}
}

// adding crm multi field to result array
if (isset($arResult['CONTACT_ID']) && !empty($arResult['CONTACT_ID']))
{
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arResult['CONTACT_ID']));
	while($ar = $res->Fetch())
	{
		if (!$isInExportMode)
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['CONTACT'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}

	foreach ($arFmList as $elementId => $arFM)
	{
		foreach ($arFM as $complexId => $arComplexName)
		{
			$arResult['CONTACT'][$elementId][$complexId] = implode(', ', $arComplexName);
		}
	}

	// checking access for operation
	$arContactAttr = CCrmPerms::GetEntityAttr('CONTACT', $arResult['CONTACT_ID']);
	foreach ($arResult['CONTACT_ID'] as $iContactId)
	{
		$arResult['CONTACT'][$iContactId]['EDIT'] = $userPermissions->CheckEnityAccess('CONTACT', 'WRITE', $arContactAttr[$iContactId]);
		$arResult['CONTACT'][$iContactId]['DELETE'] = $userPermissions->CheckEnityAccess('CONTACT', 'DELETE', $arContactAttr[$iContactId]);

		$arResult['CONTACT'][$iContactId]['BIZPROC_LIST'] = array();

		if ($isBizProcInstalled)
		{
			foreach ($arBPData as $arBP)
			{
				if (!CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$userID,
					array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arResult['CONTACT'][$iContactId]['ID']),
					array(
						'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
						'DocumentStates' => $arDocumentStates,
						'WorkflowTemplateId' => $arBP['ID'],
						'CreatedBy' => $arResult['CONTACT'][$iContactId]['~ASSIGNED_BY_ID'],
						'UserIsAdmin' => $isAdmin,
						'CRMEntityAttr' => $arContactAttr
					)
				))
				{
					continue;
				}

				$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['CONTACT'][$iContactId]['ID']
					)),
					array(
						'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
						'CRM_CONTACT_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_CONTACT_LIST'])
				);

				if (isset($arBP['HAS_PARAMETERS']))
				{
					$params = \Bitrix\Main\Web\Json::encode(array(
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentContact',
						'documentType' => 'CONTACT',
						'documentId' => 'CONTACT_'.$arResult['CONTACT'][$iContactId]['ID'],
						'templateId' => $arBP['ID'],
						'templateName' => $arBP['NAME'],
						'hasParameters' => $arBP['HAS_PARAMETERS']
					));
					$arBP['ONCLICK'] = 'BX.Bizproc.Starter.singleStart('.$params
						.', function(){BX.Main.gridManager.reload(\''.CUtil::JSEscape($arResult['GRID_ID']).'\');});';
				}

				$arResult['CONTACT'][$iContactId]['BIZPROC_LIST'][] = $arBP;
			}
		}
	}
}

if (is_array($arResult['CONTACT_ID']) && !empty($arResult['CONTACT_ID']))
{
	if ($isInExportMode && $isStExport && $isStExportRequisiteMultiline)
	{
		$requisiteExportInfo =
			$requisite->prepareEntityListRequisiteExportData(CCrmOwnerType::Contact, $arResult['CONTACT_ID']);
		$arResult['STEXPORT_RQ_HEADERS'] = $requisiteExportInfo['HEADERS'];
		$requisiteExportData = $requisiteExportInfo['EXPORT_DATA'];
		unset($requisiteExportInfo);
		$arResult['STEXPORT_RQ_DATA'] = $requisite->entityListRequisiteExportDataFormatMultiline(
			$requisiteExportData,
			$arResult['STEXPORT_RQ_HEADERS'],
			array('EXPORT_TYPE' => $sExportType)
		);
		unset($requisiteExportData);
	}
	else
	{
		$requisite->prepareEntityListFieldsValues(
			$arResult['CONTACT'],
			CCrmOwnerType::Contact,
			$arResult['CONTACT_ID'],
			$rqSelect,
			array('EXPORT_TYPE' => $sExportType)
		);
	}
}

if (!$isInExportMode)
{
	$arResult['ANALYTIC_TRACKER'] = array(
		'lead_enabled' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isEnabled() ? 'Y' : 'N'
	);

	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] =
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
		$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] =
		$arResult['NEED_FOR_TRANSFER_REQUISITES'] =
		$arResult['NEED_FOR_BUILD_TIMELINE'] =
		$arResult['NEED_FOR_BUILD_DUPLICATE_INDEX'] =
		$arResult['NEED_TO_CONVERT_ADDRESSES'] =
		$arResult['NEED_TO_CONVERT_UF_ADDRESSES'] =
		$arResult['NEED_TO_CONVERT_UF_ADDRESSES'] =
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] = false;

	if(!$bInternal)
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_SEARCH_CONTENT', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
		}

		$arResult['NEED_FOR_BUILD_TIMELINE'] = \Bitrix\Crm\Agent\Timeline\ContactTimelineBuildAgent::getInstance()->isEnabled();

		$attributeRebuildAgent = \Bitrix\Crm\Agent\Security\ContactAttributeRebuildAgent::getInstance();
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] =
			$attributeRebuildAgent->isEnabled()
			&& ($attributeRebuildAgent->getProgressData()['TOTAL_ITEMS'] > 0)
		;

		$agent = Bitrix\Crm\Agent\Duplicate\ContactDuplicateIndexRebuildAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				$agent->enable(false);
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_FOR_BUILD_DUPLICATE_INDEX'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);

		if(CCrmPerms::IsAdmin())
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX', 'N') === 'Y')
			{
				$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
				$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT', 'N') === 'Y')
			{
				$arResult['NEED_FOR_TRANSFER_REQUISITES'] = true;
			}
		}

		//region Address conversion
		/** @var ContactAddressConvertAgent $agent */
		$agent = ContactAddressConvertAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				$agent->enable(false);
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_TO_CONVERT_ADDRESSES'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);
		//endregion Address conversion

		//region Transfer addresses from user fields
		/** @var ContactUfAddressConvertAgent $agent */
		$agent = ContactUfAddressConvertAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				if (CCrmOwnerType::IsDefined($agent->getSourceEntityTypeId()))
				{
					// Disable if was running but is not active
					// Source entity type is known only after start the agent
					$agent->enable(false);
				}
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_TO_CONVERT_UF_ADDRESSES'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);
		//endregion Transfer addresses from user fields

		//region Show the process of indexing duplicates
		$agent = ContactIndexRebuild::getInstance($userID);
		$isNeedToShowDupIndexProcess = false;
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === ContactIndexRebuild::STATUS_RUNNING)
			{
				$isNeedToShowDupIndexProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'] = $isNeedToShowDupIndexProcess;
		unset($isNeedToShowDupIndexProcess, $agent);
		//endregion Show the process of indexing duplicates

		//region Show the process of merge duplicates
		$isNeedToShowDupMergeProcess = false;
		$agent = ContactMerge::getInstance($userID);
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === ContactMerge::STATUS_RUNNING)
			{
				$isNeedToShowDupMergeProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'] = $isNeedToShowDupMergeProcess;
		unset($isNeedToShowDupMergeProcess, $agent);
		//endregion Show the process of merge duplicates
	}

	//region Show the progress of data preparing for volatile duplicate types
	$isNeedToShowDupVolDataPrepare = false;
	$typeInfo = Volatile\TypeInfo::getInstance()->getIdsByEntityTypes([CCrmOwnerType::Contact]);
	if (isset($typeInfo[CCrmOwnerType::Contact]))
	{
		foreach ($typeInfo[CCrmOwnerType::Contact] as $id)
		{
			$agent = IndexRebuild::getInstance($id);
			if ($agent->isActive())
			{
				$state = $agent->state()->getData();
				/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
				if (isset($state['STATUS']) && $state['STATUS'] === IndexRebuild::STATUS_RUNNING)
				{
					$isNeedToShowDupVolDataPrepare = true;
				}
			}
		}
	}
	$arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'] = $isNeedToShowDupVolDataPrepare;
	unset($isNeedToShowDupVolDataPrepare, $typeInfo, $id, $agent, $state);
	//endregion Show the progress of data preparing for volatile duplicate types

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['CONTACT']),
			'TOTAL_ITEMS' => $arResult['STEXPORT_TOTAL_ITEMS']
		);
	}
	else
	{
		$APPLICATION->RestartBuffer();
		// hack. any '.default' customized template should contain 'excel' page
		$this->__templateName = '.default';

		if ($sExportType === 'carddav')
		{
			Header('Content-Type: text/vcard');
		}
		elseif ($sExportType === 'csv')
		{
			Header('Content-Type: text/csv');
			Header('Content-Disposition: attachment;filename=contacts.csv');
		}
		elseif ($sExportType === 'excel')
		{
			Header('Content-Type: application/vnd.ms-excel');
			Header('Content-Disposition: attachment;filename=contacts.xls');
		}
		Header('Content-Type: application/octet-stream');
		Header('Content-Transfer-Encoding: binary');

		// add UTF-8 BOM marker
		if (defined('BX_UTF') && BX_UTF)
			echo chr(239).chr(187).chr(191);

		$this->IncludeComponentTemplate($sExportType);

		die();
	}
}
?>
