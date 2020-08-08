<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Settings\OrderSettings;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();

/** @global CMain $APPLICATION */
global $APPLICATION;

// Preparing of URL templates -->
$arParams['PATH_TO_START'] = CrmCheckPath('PATH_TO_START', isset($arParams['PATH_TO_START']) ? $arParams['PATH_TO_START'] : '', Option::get('crm', 'path_to_start', '/crm/start/', false));

$arParams['PATH_TO_ORDER_LIST'] = $arParams['PATH_TO_ORDER_LIST'] <> ''? $arParams['PATH_TO_ORDER_LIST'] : '#SITE_DIR#shop/orders/list/';
$arParams['PATH_TO_ORDER_KANBAN'] = $arParams['PATH_TO_ORDER_KANBAN'] <> ''? $arParams['PATH_TO_ORDER_KANBAN'] : '#SITE_DIR#shop/orders/kanban/';

$arParams['PATH_TO_ACTIVITY_LIST'] = (isset($arParams['PATH_TO_ACTIVITY_LIST']) && $arParams['PATH_TO_ACTIVITY_LIST'] !== '') ? $arParams['PATH_TO_ACTIVITY_LIST'] : '#SITE_DIR#crm/activity/';
$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '', '#SITE_DIR#crm/company/list/');
$arParams['PATH_TO_COMPANY_EDIT'] = (isset($arParams['PATH_TO_COMPANY_EDIT']) && $arParams['PATH_TO_COMPANY_EDIT'] !== '') ? $arParams['PATH_TO_COMPANY_EDIT'] : '#SITE_DIR#crm/company/edit/#company_id#/';
$arParams['PATH_TO_COMPANY_DETAILS'] = CrmCheckPath('PATH_TO_COMPANY_DETAILS', $arParams['PATH_TO_COMPANY_DETAILS'], '#SITE_DIR#crm/company/details/#company_id#/');

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '', '#SITE_DIR#crm/contact/list/');
$arParams['PATH_TO_CONTACT_EDIT'] = (isset($arParams['PATH_TO_CONTACT_EDIT']) && $arParams['PATH_TO_CONTACT_EDIT'] !== '') ? $arParams['PATH_TO_CONTACT_EDIT'] : '#SITE_DIR#crm/contact/edit/#contact_id#/';
$arParams['PATH_TO_CONTACT_DETAILS'] = CrmCheckPath('PATH_TO_CONTACT_DETAILS', $arParams['PATH_TO_CONTACT_DETAILS'], '#SITE_DIR#crm/contact/details/#contact_id#/');

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '', '#SITE_DIR#crm/deal/list/');
$arParams['PATH_TO_DEAL_EDIT'] = (isset($arParams['PATH_TO_DEAL_EDIT']) && $arParams['PATH_TO_DEAL_EDIT'] !== '') ? $arParams['PATH_TO_DEAL_EDIT'] : '#SITE_DIR#crm/deal/edit/#deal_id#/';
$arParams['PATH_TO_DEAL_KANBAN'] = (isset($arParams['PATH_TO_DEAL_KANBAN']) && $arParams['PATH_TO_DEAL_KANBAN'] !== '') ? $arParams['PATH_TO_DEAL_KANBAN'] : '#SITE_DIR#crm/deal/kanban/';
$arParams['PATH_TO_DEAL_CALENDAR'] = (isset($arParams['PATH_TO_DEAL_CALENDAR']) && $arParams['PATH_TO_DEAL_CALENDAR'] !== '') ? $arParams['PATH_TO_DEAL_CALENDAR'] : '#SITE_DIR#crm/deal/calendar/';
$arParams['PATH_TO_DEAL_KANBANCATEGORY'] = (isset($arParams['PATH_TO_DEAL_KANBANCATEGORY']) && $arParams['PATH_TO_DEAL_KANBANCATEGORY'] !== '') ? $arParams['PATH_TO_DEAL_KANBANCATEGORY'] : '#SITE_DIR#crm/deal/kanban/category/#category_id#/';
$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] = (isset($arParams['PATH_TO_DEAL_CALENDARCATEGORY']) && $arParams['PATH_TO_DEAL_CALENDARCATEGORY'] !== '') ? $arParams['PATH_TO_DEAL_CALENDARCATEGORY'] : '#SITE_DIR#crm/deal/calendar/category/#category_id#/';
$arParams['PATH_TO_DEAL_CATEGORY'] = (isset($arParams['PATH_TO_DEAL_CATEGORY']) && $arParams['PATH_TO_DEAL_CATEGORY'] !== '') ? $arParams['PATH_TO_DEAL_CATEGORY'] : '#SITE_DIR#crm/deal/category/#category_id#/';
$arParams['PATH_TO_DEAL_DETAILS'] = CrmCheckPath('PATH_TO_DEAL_DETAILS', $arParams['PATH_TO_DEAL_DETAILS'], '#SITE_DIR#crm/deal/details/#deal_id#/');

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '', '#SITE_DIR#crm/lead/list/');
$arParams['PATH_TO_LEAD_EDIT'] = (isset($arParams['PATH_TO_LEAD_EDIT']) && $arParams['PATH_TO_LEAD_EDIT'] !== '') ? $arParams['PATH_TO_LEAD_EDIT'] : '#SITE_DIR#crm/lead/edit/#lead_id#/';
$arParams['PATH_TO_LEAD_KANBAN'] = (isset($arParams['PATH_TO_LEAD_KANBAN']) && $arParams['PATH_TO_LEAD_KANBAN'] !== '') ? $arParams['PATH_TO_LEAD_KANBAN'] : '#SITE_DIR#crm/lead/kanban/';
$arParams['PATH_TO_LEAD_CALENDAR'] = (isset($arParams['PATH_TO_LEAD_CALENDAR']) && $arParams['PATH_TO_LEAD_CALENDAR'] !== '') ? $arParams['PATH_TO_LEAD_CALENDAR'] : '#SITE_DIR#crm/lead/calendar/';
$arParams['PATH_TO_LEAD_DETAILS'] = CrmCheckPath('PATH_TO_LEAD_DETAILS', $arParams['PATH_TO_LEAD_DETAILS'], '#SITE_DIR#crm/lead/details/#lead_id#/');

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', isset($arParams['PATH_TO_QUOTE_LIST']) ? $arParams['PATH_TO_QUOTE_LIST'] : '', '#SITE_DIR#crm/quote/list/');
$arParams['PATH_TO_QUOTE_EDIT'] = (isset($arParams['PATH_TO_QUOTE_EDIT']) && $arParams['PATH_TO_QUOTE_EDIT'] !== '') ? $arParams['PATH_TO_QUOTE_EDIT'] : '#SITE_DIR#crm/quote/edit/#quote_id#/';
$arParams['PATH_TO_QUOTE_KANBAN'] = (isset($arParams['PATH_TO_QUOTE_KANBAN']) && $arParams['PATH_TO_QUOTE_KANBAN'] !== '') ? $arParams['PATH_TO_QUOTE_KANBAN'] : '#SITE_DIR#crm/quote/kanban/';
$arParams['PATH_TO_QUOTE_DETAILS'] = CrmCheckPath('PATH_TO_QUOTE_DETAILS', $arParams['PATH_TO_QUOTE_DETAILS'], '#SITE_DIR#crm/quote/details/#quote_id#/');

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', isset($arParams['PATH_TO_INVOICE_LIST']) ? $arParams['PATH_TO_INVOICE_LIST'] : '', '#SITE_DIR#crm/invoice/list/');
$arParams['PATH_TO_INVOICE_RECUR'] = CrmCheckPath('PATH_TO_INVOICE_RECUR', isset($arParams['PATH_TO_INVOICE_RECUR']) ? $arParams['PATH_TO_INVOICE_RECUR'] : '', '#SITE_DIR#crm/invoice/recur/');
$arParams['PATH_TO_INVOICE_EDIT'] = (isset($arParams['PATH_TO_INVOICE_EDIT']) && $arParams['PATH_TO_INVOICE_EDIT'] !== '') ? $arParams['PATH_TO_INVOICE_EDIT'] : '#SITE_DIR#crm/invoice/edit/#invoice_id#/';
$arParams['PATH_TO_INVOICE_KANBAN'] = (isset($arParams['PATH_TO_INVOICE_KANBAN']) && $arParams['PATH_TO_INVOICE_KANBAN'] !== '') ? $arParams['PATH_TO_INVOICE_KANBAN'] : '#SITE_DIR#crm/invoice/kanban/';
$arParams['PATH_TO_REPORT_LIST'] = (isset($arParams['PATH_TO_REPORT_LIST']) && $arParams['PATH_TO_REPORT_LIST'] !== '') ? $arParams['PATH_TO_REPORT_LIST'] : '#SITE_DIR#crm/reports/report/';
$arParams['PATH_TO_DEAL_FUNNEL'] = (isset($arParams['PATH_TO_DEAL_FUNNEL']) && $arParams['PATH_TO_DEAL_FUNNEL'] !== '') ? $arParams['PATH_TO_DEAL_FUNNEL'] : '#SITE_DIR#crm/reports/';
$arParams['PATH_TO_EVENT_LIST'] = (isset($arParams['PATH_TO_EVENT_LIST']) && $arParams['PATH_TO_EVENT_LIST'] !== '') ? $arParams['PATH_TO_EVENT_LIST'] : '#SITE_DIR#crm/events/';
$arParams['PATH_TO_PRODUCT_LIST'] = (isset($arParams['PATH_TO_PRODUCT_LIST']) && $arParams['PATH_TO_PRODUCT_LIST'] !== '') ? $arParams['PATH_TO_PRODUCT_LIST'] : '#SITE_DIR#crm/product/index.php';
$arParams['PATH_TO_CATALOG'] = (isset($arParams['PATH_TO_CATALOG']) && $arParams['PATH_TO_CATALOG'] !== '') ? $arParams['PATH_TO_CATALOG'] : '#SITE_DIR#crm/catalog/';
$arParams['PATH_TO_SETTINGS'] = (isset($arParams['PATH_TO_SETTINGS']) && $arParams['PATH_TO_SETTINGS'] !== '') ? $arParams['PATH_TO_SETTINGS'] : '#SITE_DIR#crm/configs/';
$arParams['PATH_TO_SEARCH_PAGE'] = (isset($arParams['PATH_TO_SEARCH_PAGE']) && $arParams['PATH_TO_SEARCH_PAGE'] !== '') ? $arParams['PATH_TO_SEARCH_PAGE'] : '#SITE_DIR#search/index.php?where=crm';
$arParams['PATH_TO_PRODUCT_MARKETPLACE'] = (isset($arParams['PATH_TO_PRODUCT_MARKETPLACE']) && $arParams['PATH_TO_PRODUCT_MARKETPLACE'] !== '') ? $arParams['PATH_TO_PRODUCT_MARKETPLACE'] : '#SITE_DIR#marketplace/category/crm/';
$arParams['PATH_TO_WEBFORM'] = (isset($arParams['PATH_TO_WEBFORM']) && $arParams['PATH_TO_WEBFORM'] !== '') ? $arParams['PATH_TO_WEBFORM'] : '#SITE_DIR#crm/webform/';
$arParams['PATH_TO_INVOICING'] = (isset($arParams['PATH_TO_INVOICING']) && $arParams['PATH_TO_INVOICING'] !== '') ? $arParams['PATH_TO_INVOICING'] : '#SITE_DIR#crm/invoicing/';
$arParams['PATH_TO_BUTTON'] = (isset($arParams['PATH_TO_BUTTON']) && $arParams['PATH_TO_BUTTON'] !== '') ? $arParams['PATH_TO_BUTTON'] : '#SITE_DIR#crm/button/';
$arParams['PATH_TO_CRMPLUS'] = (isset($arParams['PATH_TO_CRMPLUS']) && $arParams['PATH_TO_CRMPLUS'] !== '') ? $arParams['PATH_TO_CRMPLUS'] : '#SITE_DIR#crm/crmplus/';
$arParams['PATH_TO_RECYCLE_BIN'] = CrmCheckPath('PATH_TO_RECYCLE_BIN', isset($arParams['PATH_TO_RECYCLE_BIN']) ? $arParams['PATH_TO_RECYCLE_BIN'] : '', '#SITE_DIR#crm/recyclebin/');

$currentCategoryID = CUserOptions::GetOption('crm', 'current_deal_category', -1);
if($currentCategoryID >= 0)
{
	$arParams['PATH_TO_DEAL_LIST'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_CATEGORY'],
		array('category_id' => $currentCategoryID)
	);
	$arParams['PATH_TO_DEAL_KANBAN'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_KANBANCATEGORY'],
		array('category_id' => $currentCategoryID)
	);
}

// set default view from settings

$defaultViews = array(
	'DEAL' => array(
		DealSettings::VIEW_LIST => $arParams['PATH_TO_DEAL_LIST'],
		DealSettings::VIEW_KANBAN => $arParams['PATH_TO_DEAL_KANBAN'],
		DealSettings::VIEW_CALENDAR => $arParams['PATH_TO_DEAL_CALENDAR']
	),
	'LEAD' => array(
		LeadSettings::VIEW_LIST => $arParams['PATH_TO_LEAD_LIST'],
		LeadSettings::VIEW_KANBAN => $arParams['PATH_TO_LEAD_KANBAN'],
		LeadSettings::VIEW_CALENDAR => $arParams['PATH_TO_LEAD_CALENDAR']
	),
	'INVOICE' => array(
		InvoiceSettings::VIEW_LIST => $arParams['PATH_TO_INVOICE_LIST'],
		InvoiceSettings::VIEW_KANBAN => $arParams['PATH_TO_INVOICE_KANBAN']
	),
	'ORDER' => array(
		OrderSettings::VIEW_LIST => $arParams['PATH_TO_ORDER_LIST'],
		OrderSettings::VIEW_KANBAN => $arParams['PATH_TO_ORDER_KANBAN']
	),
	'QUOTE' => array(
		QuoteSettings::VIEW_LIST => $arParams['PATH_TO_QUOTE_LIST'],
		QuoteSettings::VIEW_KANBAN => $arParams['PATH_TO_QUOTE_KANBAN']
	),
	'COMPANY' => array(
		CompanySettings::VIEW_LIST => $arParams['PATH_TO_COMPANY_LIST'],
	),
	'CONTACT' => array(
		ContactSettings::VIEW_LIST => $arParams['PATH_TO_CONTACT_LIST'],
	),
	'ACTIVITY' => array(
		ActivitySettings::VIEW_LIST => $arParams['PATH_TO_ACTIVITY_LIST'],
	)
);

foreach ($defaultViews as $vewCode => $viewPath)
{
	$settingsClass = '\\Bitrix\\Crm\\Settings\\' . $vewCode . 'Settings';
	$defaultView = $settingsClass::getCurrent()->getDefaultListViewID();
	if (isset($viewPath[$defaultView]))
	{
		$arParams['PATH_TO_' . $vewCode . '_INDEX'] = $viewPath[$defaultView];
	}
	else
	{
		$arParams['PATH_TO_' . $vewCode . '_INDEX'] = $arParams['PATH_TO_' . $vewCode . '_LIST'];
	}
}

$navigationIndex = CUserOptions::GetOption('crm.navigation', 'index');
if(is_array($navigationIndex))
{
	foreach($navigationIndex as $k => $v)
	{
		$parts = explode(':', $v);
		if(is_array($parts) && count($parts) >= 2)
		{
			$page = $parts[0];
		}
		else
		{
			$page = $v;
		}

		//At present time setting date are ignored
		$arParams['PATH_TO_'.mb_strtoupper($k).'_INDEX'] = $arParams['PATH_TO_'.mb_strtoupper($k.'_'.$page)];
	}
}
//<-- Preparing of URL templates

$isSliderEnabled = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

$arResult['ACTIVE_ITEM_ID'] = isset($arParams['ACTIVE_ITEM_ID']) ? $arParams['ACTIVE_ITEM_ID'] : '';
//$arResult['ENABLE_SEARCH'] = isset($arParams['ENABLE_SEARCH']) && is_bool($arParams['ENABLE_SEARCH']) ? $arParams['ENABLE_SEARCH'] : true ;
$arResult['ENABLE_SEARCH'] = false;
$arResult['SEARCH_PAGE_URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SEARCH_PAGE']);

$arResult['ID'] = isset($arParams['ID']) ? $arParams['ID'] : '';
if($arResult['ID'] === '')
{
	$arResult['ID'] = 'DEFAULT';
}

$isAdmin = CCrmPerms::IsAdmin();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$enableIdleCounter = CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true);
$counterExtras = isset($arParams['COUNTER_EXTRAS']) && is_array($arParams['COUNTER_EXTRAS'])
	? $arParams['COUNTER_EXTRAS'] : array();

// Prepere standard items -->
$isLeadEnabled = \Bitrix\Crm\Settings\LeadSettings::isEnabled();
$stdItems = array();
$leadItem = array();

if($isAdmin || CCrmLead::CheckReadPermission(0, $userPermissions))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Lead,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || CCrmLead::CheckCreatePermission($userPermissions))
	{
		if($isSliderEnabled)
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LEAD_DETAILS'],
				array('lead_id' => 0)
			);
		}
		else
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LEAD_EDIT'],
				array('lead_id' => 0)
			);
		}

		$actions[] = array('ID' => 'CREATE', 'URL' => $createUrl);
	}

	$leadItem = array(
		'ID' => 'LEAD',
		'MENU_ID' => 'menu_crm_lead',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			 isset($arParams['PATH_TO_LEAD_INDEX']) && $arParams['PATH_TO_LEAD_INDEX'] !== ''
				 ? $arParams['PATH_TO_LEAD_INDEX'] : $arParams['PATH_TO_LEAD_LIST']
		),
		'ICON' => 'lead',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions,
		'IS_DISABLED' => !$isLeadEnabled
	);
}

if (!empty($leadItem) && $isLeadEnabled)
{
	$stdItems["LEAD"] = $leadItem;
}

if($isAdmin || CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Deal,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || CCrmDeal::CheckCreatePermission($userPermissions, $currentCategoryID))
	{
		if($isSliderEnabled)
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_DETAILS'],
				array('deal_id' => 0)
			);
		}
		else
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_EDIT'],
				array('deal_id' => 0)
			);
		}

		if($currentCategoryID >= 0)
		{
			$createUrl = CCrmUrlUtil::AddUrlParams($createUrl, array('category_id' => $currentCategoryID));
		}

		$actions[] = array('ID' => 'CREATE', 'URL' => $createUrl);
	}

	$stdItems['DEAL'] = array(
		'ID' => 'DEAL',
		'MENU_ID' => 'menu_crm_deal',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_DEAL_INDEX']) && $arParams['PATH_TO_DEAL_INDEX'] !== ''
				? $arParams['PATH_TO_DEAL_INDEX'] : $arParams['PATH_TO_DEAL_LIST']
		),
		'ICON' => 'deal',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions
	);
}

if($isAdmin || CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Contact,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || CCrmContact::CheckCreatePermission($userPermissions))
	{
		if($isSliderEnabled)
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_DETAILS'],
				array('contact_id' => 0)
			);
		}
		else
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_EDIT'],
				array('contact_id' => 0)
			);
		}

		$actions[] = array('ID' => 'CREATE', 'URL' => $createUrl);
	}

	$stdItems['CONTACT'] = array(
		'ID' => 'CONTACT',
		'MENU_ID' => 'menu_crm_contact',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_CONTACT_INDEX']) && $arParams['PATH_TO_CONTACT_INDEX'] !== ''
				? $arParams['PATH_TO_CONTACT_INDEX'] : $arParams['PATH_TO_CONTACT_LIST']
		),
		'ICON' => 'contact',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions
	);
}

if($isAdmin || CCrmCompany::CheckReadPermission(0, $userPermissions))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Company,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || CCrmCompany::CheckCreatePermission($userPermissions))
	{
		if($isSliderEnabled)
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_COMPANY_DETAILS'],
				array('company_id' => 0)
			);
		}
		else
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_COMPANY_EDIT'],
				array('company_id' => 0)
			);
		}

		$actions[] = array('ID' => 'CREATE', 'URL' => $createUrl);
	}

	$stdItems['COMPANY'] = array(
		'ID' => 'COMPANY',
		'MENU_ID' => 'menu_crm_company',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_COMPANY_INDEX']) && $arParams['PATH_TO_COMPANY_INDEX'] !== ''
				? $arParams['PATH_TO_COMPANY_INDEX'] : $arParams['PATH_TO_COMPANY_LIST']
		),
		'ICON' => 'company',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions
	);
}


if (\Bitrix\Main\Loader::includeModule('report') && \Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	$stdItems['ANALYTICS'] = [
		'ID' => 'ANALYTICS',
		'MENU_ID' => 'menu_crm_analytics',
		'NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS'),
		'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_TITLE'),
		'URL' => SITE_DIR."report/analytics/",
	];
}

if ($isAdmin || $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	if (\Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Catalog\Config\State::isProductCardSliderEnabled())
	{
		$stdItems['CATALOGUE'] = array(
			'ID' => 'CATALOG',
			'MENU_ID' => 'menu_crm_catalog',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
			'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
			'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CATALOG']),
			'ICON' => 'catalog'
		);
	}
	else
	{
		$stdItems['CATALOGUE'] = array(
			'ID' => 'PRODUCT',
			'MENU_ID' => 'menu_crm_product',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
			'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
			'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_LIST']),
			'ICON' => 'catalog'
		);
	}
}

if (\Bitrix\Main\Config\Option::get("crm", "crm_shop_enabled", "N") === 'Y')
{
	$stdItems['ORDER'] = array(
		'ID' => 'ORDER',
		'MENU_ID' => 'menu_crm_order',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_ORDER'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_ORDER'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			 isset($arParams['PATH_TO_ORDER_INDEX']) && $arParams['PATH_TO_ORDER_INDEX'] !== ''
				 ? $arParams['PATH_TO_ORDER_INDEX'] : $arParams['PATH_TO_ORDER_LIST']
		),
	);
}

$stdItems['SETTINGS'] = array(
	'ID' => 'SETTINGS',
	'MENU_ID' => 'menu_crm_configs',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'), //title
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SETTINGS']),
	'ICON' => 'settings'
);

if($isAdmin || !$userPermissions->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	$stdItems['INVOICE'] = array(
		'ID' => 'INVOICE',
		'MENU_ID' => 'menu_crm_invoice',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICE'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICE_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_INVOICE_INDEX']) && $arParams['PATH_TO_INVOICE_INDEX'] !== ''
				? $arParams['PATH_TO_INVOICE_INDEX'] : $arParams['PATH_TO_INVOICE_LIST']
		),
		'ICON' => 'invoice',
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' =>  CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'],
					array('invoice_id' => 0)
				)
			)
		),
		'IS_DISABLED' => true
	);

	if (IsModuleInstalled('bitrix24'))
	{
		if (CModule::IncludeModule('sale'))
		{
			$dbRes = \Bitrix\Sale\PaySystem\Manager::getList([
																 'select' => ['ID'],
																 'filter' => ['ACTION_FILE' => 'alfabankb2b']
															 ]);
			while ($data = $dbRes->fetch())
			{
				$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($data['ID']);
				if ($service && $service->isTuned())
				{
					$stdItems['INVOICING'] = array(
						'ID' => 'INVOICING',
						'MENU_ID' => 'menu_crm_invoicing',
						'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICING'),
						'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICING'),
						'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICING']),
						'ICON' => 'invoicing',
						'IS_DISABLED' => true
					);

					break;
				}
			}
		}
	}
}

if($isAdmin || CCrmQuote::CheckReadPermission(0, $userPermissions))
{
	$actions = array();
	if($isAdmin || CCrmQuote::CheckCreatePermission($userPermissions))
	{
		//if($isSliderEnabled)
		//{
		//	$createUrl = CComponentEngine::MakePathFromTemplate(
		//		$arParams['PATH_TO_QUOTE_DETAILS'],
		//		array('quote_id' => 0)
		//	);
		//}
		//else
		//{
		$createUrl = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_QUOTE_EDIT'],
			array('quote_id' => 0)
		);
		//}

		$actions[] = array('ID' => 'CREATE', 'URL' => $createUrl);
	}

	$stdItems['QUOTE'] = array(
		'ID' => 'QUOTE',
		'MENU_ID' => 'menu_crm_quote',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_QUOTE_INDEX']) && $arParams['PATH_TO_QUOTE_INDEX'] !== ''
				? $arParams['PATH_TO_QUOTE_INDEX'] : $arParams['PATH_TO_QUOTE_LIST']
		),
		'ICON' => 'quote',
		'ACTIONS' => $actions,
		'IS_DISABLED' => true
	);
}

$stdItems['RECYCLE_BIN'] = array(
	'ID' => 'RECYCLE_BIN',
	'MENU_ID' => 'menu_crm_recycle_bin',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_RECYCLE_BIN'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_RECYCLE_BIN_TITLE'),
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_RECYCLE_BIN']),
	'ICON' => 'recycle_bin',
	'IS_DISABLED' => true
);

if($isAdmin || CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	$stdItems['DEAL_FUNNEL'] = array(
		'ID' => 'DEAL_FUNNEL',
		'MENU_ID' => 'menu_crm_funel',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL'),
		'BRIEF_NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL_BRIEF'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_FUNNEL']),
		'ICON' => 'funnel',
		'IS_DISABLED' => true
	);
}

if(IsModuleInstalled('report'))
{
	$stdItems['REPORT'] = array(
		'ID' => 'REPORT',
		'MENU_ID' => 'menu_crm_report',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REPORT_LIST']),
		'ICON' => 'report',
		'IS_DISABLED' => true
	);
}

$stdItems['EVENT'] = array(
	'ID' => 'EVENT',
	'MENU_ID' => 'menu_crm_event',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'), //title
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_EVENT_LIST']),
	'ICON' => 'event',
	'IS_DISABLED' => true
);

if($isAdmin || !$userPermissions->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'READ'))
{
	$stdItems['WEBFORM'] = array(
		'ID' => 'WEBFORM',
		'MENU_ID' => 'menu_crm_webform',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_WEBFORM'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_WEBFORM'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_WEBFORM']),
		'ICON' => 'webform',
		'IS_DISABLED' => true
	);
}

if($isAdmin || !$userPermissions->HavePerm('BUTTON', BX_CRM_PERM_NONE, 'READ'))
{
	$stdItems['SITEBUTTON'] = array(
		'ID' => 'SITEBUTTON',
		'MENU_ID' => 'menu_crm_button',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_BUTTON'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_BUTTON'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_BUTTON']),
		'ICON' => 'sitebutton',
		'IS_DISABLED' => true
	);
}

$stdItems['START'] = array(
	'ID' => 'START',
	'MENU_ID' => 'menu_crm_start',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_START'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_START_TITLE'),
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_START']),
	'IS_DISABLED' => true
);

$stdItems['MY_ACTIVITY'] = array(
	'ID' => 'MY_ACTIVITY',
	'MENU_ID' => 'menu_crm_activity',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY_TITLE'),
	'URL' => CComponentEngine::MakePathFromTemplate(
		isset($arParams['PATH_TO_ACTIVITY_INDEX']) && $arParams['PATH_TO_ACTIVITY_INDEX'] !== ''
			? $arParams['PATH_TO_ACTIVITY_INDEX'] : $arParams['PATH_TO_ACTIVITY_LIST']
	),
	'ICON' => 'activity',
	'IS_DISABLED' => true
);

$stdItems['STREAM'] = array(
	'ID' => 'STREAM',
	'MENU_ID' => 'menu_crm_stream',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_STREAM'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_STREAM_TITLE'),
	'URL' =>  CComponentEngine::MakePathFromTemplate(
		isset($arParams['PATH_TO_STREAM']) ? $arParams['PATH_TO_STREAM'] : '#SITE_DIR#crm/stream/'
	),
	'ICON' => 'feed',
	'IS_DISABLED' => true
);

if (!empty($leadItem) && !$isLeadEnabled)
{
	$stdItems["LEAD"] = $leadItem;
}

if(ModuleManager::isModuleInstalled('bitrix24'))
{
	$stdItems['MARKETPLACE'] = array(
		'ID' => 'MARKETPLACE',
		'MENU_ID' => 'menu_crm_marketplace',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MARKETPLACE'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MARKETPLACE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_MARKETPLACE']),
		'ICON' => 'apps',
		'IS_DISABLED' => true
	);
}

if (\Bitrix\Main\Loader::includeModule('bitrix24') && in_array(\CBitrix24::getLicenseType(), array("project", "tf")))
{
	$stdItems['CRMPLUS'] = array(
		'ID' => 'CRMPLUS',
		'MENU_ID' => 'menu_crm_plus',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CRMPLUS'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CRMPLUS'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CRMPLUS']),
		'ICON' => 'crmplus',
		'IS_DISABLED' => true
	);
}
// <-- Prepere standard items

$items = array();
$itemInfos = isset($arParams['ITEMS']) && is_array($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
if(empty($itemInfos))
{
	$items = array_values($stdItems);
}
else
{
	foreach($itemInfos as &$itemInfo)
	{
		$itemID = isset($itemInfo['ID'])? mb_strtoupper($itemInfo['ID']) : '';
		if(isset($stdItems[$itemID]))
		{
			$item = $stdItems[$itemID];
			$items[] = $item;
		}
		else
		{
			$items[] = array(
				'ID' => $itemID,
				'MENU_ID' => "menu_crm_".mb_strtolower($itemID),
				'NAME' => isset($itemInfo['NAME']) ? $itemInfo['NAME'] : $itemID,
				'URL' => isset($itemInfo['URL']) ? $itemInfo['URL'] : '',
				'COUNTER' => isset($itemInfo['COUNTER']) ? intval($itemInfo['COUNTER']) : 0,
				'COUNTER_ID' => isset($itemInfo['COUNTER_ID']) ? $itemInfo['COUNTER_ID'] : "",
				'ICON' => isset($itemInfo['ICON']) ? $itemInfo['ICON'] : ''
			);
		}
	}
	unset($itemInfo);
}


$events = GetModuleEvents('crm', 'OnAfterCrmControlPanelBuild');
while($event = $events->Fetch())
{
	ExecuteModuleEventEx($event, array(&$items));
}

$arResult['ADDITIONAL_ITEM'] = array(
	'ID' => 'MORE',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE_TITLE'),
	'ICON' => 'more'
);

$options = CUserOptions::GetOption('crm.control.panel', mb_strtolower($arResult['ID']));
if (!$options)
{
	$options = array('fixed' => 'N');
}
$arResult['IS_FIXED'] = isset($options['fixed']) && $options['fixed'] === 'Y';

if (isset($arParams["MENU_MODE"]) && $arParams["MENU_MODE"] === "Y")
{
	$arResult['ITEMS'] = array();
	foreach ($items as $key => $item)
	{

		$arResult['ITEMS'][] = array(
			$item["NAME"],
			$item["URL"],
			array(),
			$options
		);
	}

	return $arResult;
}
else
{
	$arResult['ITEMS'] = &$items;
	unset($items);
	$this->IncludeComponentTemplate();
}