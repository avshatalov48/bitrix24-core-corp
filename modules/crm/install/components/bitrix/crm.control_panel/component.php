<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Store\EnableWizard\Manager;
use Bitrix\Catalog\v2\Contractor;
use Bitrix\Crm;
use Bitrix\Crm\Component\ControlPanel\ControlPanelMenuMapper;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Integration\Market\Router;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Settings\OrderSettings;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\SalesCenter;
use Bitrix\SalesCenter\Integration\SaleManager;

/** @var CrmControlPanel $this */

$isMenuMode = isset($arParams['MENU_MODE']) && $arParams['MENU_MODE'] === 'Y';
$isGetResult = isset($arParams['GET_RESULT']) && $arParams['GET_RESULT'] === 'Y';
$isShowOutput = ($isMenuMode || $isGetResult) === false;

$userPermissionsService = Container::getInstance()->getUserPermissions();

if (!CModule::IncludeModule('crm'))
{
	if ($isShowOutput)
	{
		ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	}

	return;
}

if (!$userPermissionsService->entityType()->canReadSomeItemsInCrm())
{
	if ($isShowOutput)
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	}

	return;
}

$router = Container::getInstance()->getRouter();

/** @global CMain $APPLICATION */
global $APPLICATION;

// Preparing of URL templates -->
$crmPaths = [
	'PATH_TO_ACTIVITY_KANBAN' => '#SITE_DIR#crm/activity/kanban/',
	'PATH_TO_ACTIVITY_LIST' => '#SITE_DIR#crm/activity/',
	'PATH_TO_BUTTON' => '#SITE_DIR#crm/button/',
	'PATH_TO_CATALOG' => '#SITE_DIR#crm/catalog/',
	'PATH_TO_COMPANY_EDIT' => '#SITE_DIR#crm/company/edit/#company_id#/',
	'PATH_TO_CONTACT_EDIT' => '#SITE_DIR#crm/contact/edit/#contact_id#/',
	'PATH_TO_DEAL_ACTIVITY' => '#SITE_DIR#crm/deal/activity/',
	'PATH_TO_DEAL_ACTIVITYCATEGORY' => '#SITE_DIR#crm/deal/activity/category/#category_id#/',
	'PATH_TO_DEAL_CALENDAR' => '#SITE_DIR#crm/deal/calendar/',
	'PATH_TO_DEAL_CALENDARCATEGORY' => '#SITE_DIR#crm/deal/calendar/category/#category_id#/',
	'PATH_TO_DEAL_CATEGORY' => '#SITE_DIR#crm/deal/category/#category_id#/',
	'PATH_TO_DEAL_EDIT' => '#SITE_DIR#crm/deal/edit/#deal_id#/',
	'PATH_TO_DEAL_FUNNEL' => '#SITE_DIR#crm/reports/',
	'PATH_TO_DEAL_KANBAN' => '#SITE_DIR#crm/deal/kanban/',
	'PATH_TO_DEAL_KANBANCATEGORY' => '#SITE_DIR#crm/deal/kanban/category/#category_id#/',
	'PATH_TO_EVENT_LIST' => '#SITE_DIR#crm/events/',
	'PATH_TO_INVOICE_EDIT' => '#SITE_DIR#crm/invoice/edit/#invoice_id#/',
	'PATH_TO_INVOICE_KANBAN' => '#SITE_DIR#crm/invoice/kanban/',
	'PATH_TO_LEAD_ACTIVITY' => '#SITE_DIR#crm/lead/activity/',
	'PATH_TO_LEAD_CALENDAR' => '#SITE_DIR#crm/lead/calendar/',
	'PATH_TO_LEAD_EDIT' => '#SITE_DIR#crm/lead/edit/#lead_id#/',
	'PATH_TO_LEAD_KANBAN' => '#SITE_DIR#crm/lead/kanban/',
	'PATH_TO_MY_COMPANY' => '#SITE_DIR#crm/configs/mycompany/',
	'PATH_TO_ORDER_KANBAN' => '#SITE_DIR#shop/orders/kanban/',
	'PATH_TO_ORDER_LIST' => '##SITE_DIR#shop/orders/list/',
	'PATH_TO_PERMISSIONS' => '#SITE_DIR#crm/configs/perms/',
	'PATH_TO_PRODUCT_DETAILS' => '#SITE_DIR#crm/catalog/#catalog_id#/product/#product_id#/',
	'PATH_TO_PRODUCT_LIST' => '#SITE_DIR#crm/product/index.php',
	'PATH_TO_PRODUCT_MARKETPLACE' => Router::getBasePath(),
	'PATH_TO_QUOTE_DEADLINES' => '#SITE_DIR#crm/quote/deadlines/',
	'PATH_TO_QUOTE_EDIT' => '#SITE_DIR#crm/quote/edit/#quote_id#/',
	'PATH_TO_QUOTE_KANBAN' => '#SITE_DIR#crm/quote/kanban/',
	'PATH_TO_REPORT_LIST' => '#SITE_DIR#crm/reports/report/',
	'PATH_TO_SEARCH_PAGE' => '#SITE_DIR#search/index.php?where=crm',
	'PATH_TO_SETTINGS' => '#SITE_DIR#crm/configs/',
	'PATH_TO_WEBFORM' => '#SITE_DIR#crm/webform/',
];

foreach ($crmPaths as $key => $defaultPath)
{
	$arParams[$key] = !empty($arParams[$key]) ? $arParams[$key] : $defaultPath;
}

//some paths can be replaced with stored Option
$changeableCrmPaths = [
	'PATH_TO_COMPANY_DETAILS' => '#SITE_DIR#crm/company/details/#company_id#/',
	'PATH_TO_COMPANY_LIST' => '#SITE_DIR#crm/company/list/',
	'PATH_TO_CONTACT_DETAILS' => '#SITE_DIR#crm/contact/details/#contact_id#/',
	'PATH_TO_CONTACT_LIST' => '#SITE_DIR#crm/contact/list/',
	'PATH_TO_DEAL_DETAILS' => '#SITE_DIR#crm/deal/details/#deal_id#/',
	'PATH_TO_DEAL_LIST' => '#SITE_DIR#crm/deal/list/',
	'PATH_TO_INVOICE_LIST' => '#SITE_DIR#crm/invoice/list/',
	'PATH_TO_LEAD_DETAILS' => '#SITE_DIR#crm/lead/details/#lead_id#/',
	'PATH_TO_LEAD_LIST' => '#SITE_DIR#crm/lead/list/',
	'PATH_TO_QUOTE_DETAILS' => '#SITE_DIR#crm/quote/details/#quote_id#/',
	'PATH_TO_QUOTE_LIST' => '#SITE_DIR#crm/quote/list/',
	'PATH_TO_RECYCLE_BIN' => '#SITE_DIR#crm/recyclebin/',
	'PATH_TO_START' => Option::get('crm', 'path_to_start', '/crm/start/'),
];

foreach ($changeableCrmPaths as $key => $defaultPath)
{
	$arParams[$key] = CrmCheckPath($key, $arParams[$key] ?? '', $defaultPath);
}

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
	$arParams['PATH_TO_DEAL_ACTIVITY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_ACTIVITYCATEGORY'],
		['category_id' => $currentCategoryID],
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
		QuoteSettings::VIEW_KANBAN => $arParams['PATH_TO_QUOTE_KANBAN'],
		QuoteSettings::VIEW_DEADLINES => $arParams['PATH_TO_QUOTE_DEADLINES'],
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
		$pathToKey = 'PATH_TO_'.mb_strtoupper($k.'_'.$page);
		$arParams['PATH_TO_'.mb_strtoupper($k).'_INDEX'] = $arParams[$pathToKey] ?? null;
	}
}
//<-- Preparing of URL templates

$isSliderEnabled = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

$arResult['ACTIVE_ITEM_ID'] = $arParams['ACTIVE_ITEM_ID'] ?? '';

$arResult['ID'] = $arParams['ID'] ?? '';
if($arResult['ID'] === '')
{
	$arResult['ID'] = 'DEFAULT';
}

$currentUserID = $userPermissionsService->getUserId();
$isAdmin = $userPermissionsService->isAdmin();

$enableIdleCounter = CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true);
$counterExtras = isset($arParams['COUNTER_EXTRAS']) && is_array($arParams['COUNTER_EXTRAS'])
	? $arParams['COUNTER_EXTRAS'] : array();

// Prepere standard items -->
$isLeadEnabled = \Bitrix\Crm\Settings\LeadSettings::isEnabled();
$stdItems = array();
$leadItem = array();

$analyticsEventBuilder = (new Crm\Integration\Analytics\Builder\Entity\AddOpenEvent())
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
	->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CONTROL_PANEL_CREATE_BUTTON)
;

if($isAdmin || $userPermissionsService->entityType()->canReadItems(CCrmOwnerType::Lead))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Lead,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || $userPermissionsService->entityType()->canAddItems(CCrmOwnerType::Lead))
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

		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>
				$analyticsEventBuilder
					->setEntityTypeId(\CCrmOwnerType::Lead)
					->buildUri($createUrl)
					->getUri()
			,
		];
	}

	$leadItem = array(
		'ID' => 'LEAD',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId(CCrmOwnerType::Lead),
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			(isset($arParams['PATH_TO_LEAD_INDEX']) && $arParams['PATH_TO_LEAD_INDEX'] !== '')
				? $arParams['PATH_TO_LEAD_INDEX']
				: $arParams['PATH_TO_LEAD_LIST']
		),
		'ICON' => 'lead',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions,
		'IS_DISABLED' => !$isLeadEnabled,
	);
	if (!RestrictionManager::getLeadsRestriction()->hasPermission())
	{
		unset($leadItem['URL'], $leadItem['COUNTER'], $leadItem['COUNTER_ID']);
		$leadItem['IS_LOCKED'] = true;
		$leadItem['ON_CLICK'] = RestrictionManager::getLeadsRestriction()->prepareFeaturePromoterScript();
	}
}

if (!empty($leadItem) && $isLeadEnabled)
{
	$stdItems["LEAD"] = $leadItem;
}

if($isAdmin || $userPermissionsService->entityType()->canReadItems(CCrmOwnerType::Deal))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Deal,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$actions = array();
	if($isAdmin || $userPermissionsService->entityType()->canAddItemsInCategory(CCrmOwnerType::Deal, $currentCategoryID))
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

		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>
				$analyticsEventBuilder
					->setEntityTypeId(\CCrmOwnerType::Deal)
					->buildUri($createUrl)
					->getUri()
			,
		];
	}

	$stdItems['DEAL'] = array(
		'ID' => 'DEAL',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId(CCrmOwnerType::Deal),
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

if($isAdmin || $userPermissionsService->entityType()->canReadItemsInCategory(CCrmOwnerType::Contact, 0))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Contact,
		EntityCounterType::ALL,
		$currentUserID,
		array_merge(
			$counterExtras,
			[
				'CATEGORY_ID' => 0,
			]
		)
	);

	$actions = [];
	if($isAdmin || $userPermissionsService->entityType()->canAddItemsInCategory(CCrmOwnerType::Contact, 0))
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

		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>
				$analyticsEventBuilder
					->setEntityTypeId(\CCrmOwnerType::Contact)
					->buildUri($createUrl)
					->getUri()
			,
		];
	}
	$stdItems['CONTACT'] = array(
		'ID' => 'CONTACT',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId(CCrmOwnerType::Contact),
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

if($isAdmin || $userPermissionsService->entityType()->canReadItemsInCategory(CCrmOwnerType::Company, 0))
{
	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Company,
		EntityCounterType::ALL,
		$currentUserID,
		array_merge(
			$counterExtras,
			[
				'CATEGORY_ID' => 0,
			]
		)
	);

	$actions = array();
	if($isAdmin || $userPermissionsService->entityType()->canAddItemsInCategory(CCrmOwnerType::Company, 0))
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

		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>
				$analyticsEventBuilder
					->setEntityTypeId(\CCrmOwnerType::Company)
					->buildUri($createUrl)
					->getUri()
			,
		];
	}

	$stdItems['COMPANY'] = array(
		'ID' => 'COMPANY',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId(CCrmOwnerType::Company),
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
$invoiceEntityTypeId = \CCrmOwnerType::SmartInvoice;
if (InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
{
	$invoiceEntityTypeId = \CCrmOwnerType::Invoice;
}

if ($isAdmin || $userPermissionsService->entityType()->canReadItems($invoiceEntityTypeId))
{
	$actions = [];
	if ($userPermissionsService->entityType()->canAddItems($invoiceEntityTypeId))
	{
		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>  $router->getItemDetailUrl($invoiceEntityTypeId),
		];
	}
	$entityName = \CCrmOwnerType::ResolveName($invoiceEntityTypeId);
	$invoiceItem = [
		'ID' => $entityName,
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId($invoiceEntityTypeId), //'menu_crm_' . mb_strtolower($entityName),
		'NAME' => \CCrmOwnerType::GetCategoryCaption($invoiceEntityTypeId),
		'URL' => $router->getItemListUrlInCurrentView($invoiceEntityTypeId),
		'ICON' => 'invoice',
		// if we pass an empty array create button still will be displayed
		'ACTIONS' => empty($actions) ? null : $actions,
	];

	if (!RestrictionManager::getInvoicesRestriction()->hasPermission())
	{
		unset($invoiceItem['URL']);
		$invoiceItem['IS_LOCKED'] = true;
		$invoiceItem['ON_CLICK'] = RestrictionManager::getInvoicesRestriction()->prepareFeaturePromoterScript();
	}

	if (!InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
	{
		$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
			\CCrmOwnerType::SmartInvoice,
			Bitrix\Crm\Counter\EntityCounterType::ALL,
			$currentUserID,
			$counterExtras
		);

		if ($counter)
		{
			$invoiceItem['COUNTER'] = $counter->getValue();
			$invoiceItem['COUNTER_ID'] = $counter->getCode();
		}
	}

	$stdItems[\CCrmOwnerType::ResolveName($invoiceEntityTypeId)] = $invoiceItem;
}

if (
	Loader::includeModule('biconnector')
	&& class_exists('\Bitrix\BIConnector\Superset\Scope\ScopeService')
)
{
	/** @see \Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorCrm::getMenuItemData */
	$menuItem = \Bitrix\BIConnector\Superset\Scope\ScopeService::getInstance()->prepareScopeMenuItem(
		\Bitrix\BIConnector\Superset\Scope\ScopeService::BIC_SCOPE_CRM
	);
	if ($menuItem)
	{
		$stdItems['BIC_DASHBOARDS'] = $menuItem;
	}
}

if (Loader::includeModule('report') && \Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	$stdItems['ANALYTICS'] = [
		'ID' => 'ANALYTICS',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('ANALYTICS'), //'menu_crm_analytics',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS'),
		'TITLE' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_TITLE'),
		'URL' => SITE_DIR."report/analytics/",
	];
}

if (Loader::includeModule('catalog') && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isFullCatalogEnabled())
{
	$stdItems['CATALOGUE'] = array(
		'ID' => 'CATALOG',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('CATALOG'), // 'menu_crm_catalog',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_GOODS'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CATALOG']),
		'ICON' => 'catalog',
	);

	if (Catalog\Config\State::isExternalCatalog())
	{
		\Bitrix\Main\UI\Extension::load('catalog.external-catalog-stub');
		$stdItems['CATALOGUE']['ON_CLICK'] = 'event.preventDefault();BX.Catalog.ExternalCatalogStub.showCatalogStub();';
	}
}
else
{
	$stdItems['CATALOGUE'] = array(
		'ID' => 'PRODUCT',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('PRODUCT'), // 'menu_crm_catalog',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_LIST']),
		'ICON' => 'catalog'
	);
}

if (\CCrmSaleHelper::isWithOrdersMode())
{
	$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
		CCrmOwnerType::Order,
		Bitrix\Crm\Counter\EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);

	$stdItems['ORDER'] = array(
		'ID' => 'ORDER',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('ORDER'), // 'menu_crm_order',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_ORDER'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_ORDER'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			(isset($arParams['PATH_TO_ORDER_INDEX']) && $arParams['PATH_TO_ORDER_INDEX'] !== '')
			? $arParams['PATH_TO_ORDER_INDEX']
			: $arParams['PATH_TO_ORDER_LIST']
		),
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
	);
}

if (
	Loader::includeModule('catalog')
	&& AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
	&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
)
{
	\Bitrix\Main\UI\Extension::load([
		'admin_interface',
		'sidepanel'
	]);
	$stdItems['STORE_DOCUMENTS'] = [
		'ID' => 'STORE_DOCUMENTS',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('STORE_DOCUMENTS'), // 'menu_crm_store_docs',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_STORE_DOCS'),
		'TITLE' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_STORE_DOCS'),
		'URL' => SITE_DIR."shop/documents/",
		'ON_CLICK' => 'event.preventDefault();BX.SidePanel.Instance.open("/shop/documents/?inventoryManagementSource=crm", {cacheable: false, customLeftBoundary: 0,});',
	];

	if (Manager::isOnecMode())
	{
		\Bitrix\Main\UI\Extension::load('catalog.external-catalog-stub');
		$stdItems['STORE_DOCUMENTS']['ON_CLICK'] = 'event.preventDefault();BX.Catalog.ExternalCatalogStub.showDocsStub();';
	}

	if (Contractor\Provider\Manager::isActiveProviderByModule(Contractor\Provider\Manager::PROVIDER_STORE_DOCUMENT, 'crm'))
	{
		\CBitrixComponent::includeComponentClass('bitrix:catalog.store.document.control_panel');

		$contractorsCompanyItem = $this->getContractorsMenuItem(
			\CCrmOwnerType::Company,
			\CatalogStoreDocumentControlPanelComponent::PATH_TO['CONTRACTORS'],
			ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS_COMPANIES,
			$counterExtras
		);
		if ($contractorsCompanyItem)
		{
			$stdItems[$contractorsCompanyItem['ID']] = $contractorsCompanyItem;
		}

		$contractorsContactItem = $this->getContractorsMenuItem(
			\CCrmOwnerType::Contact,
			\CatalogStoreDocumentControlPanelComponent::PATH_TO['CONTRACTORS_CONTACTS'],
			ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS_CONTACTS,
			$counterExtras
		);
		if ($contractorsContactItem)
		{
			$stdItems[$contractorsContactItem['ID']] = $contractorsContactItem;
		}
	}
}

if (
	isset($this->counterPartyCategories[\CCrmOwnerType::Contact])
	&& \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
	&& \Bitrix\Main\Loader::includeModule('sign')
	&& \Bitrix\Sign\Config\Storage::instance()->isAvailable()
	&& $userPermissionsService->entityType()->canReadItemsInCategory(\CCrmOwnerType::Contact, $this->counterPartyCategories[\CCrmOwnerType::Contact])
)
{
	$counterPartyContactMenuId = $this->resolveCounterpartyMenuId(\CCrmOwnerType::Contact);

	$counter = EntityCounterFactory::create(
		\CCrmOwnerType::Contact,
		EntityCounterType::ALL,
		$currentUserID,
		array_merge(
			$counterExtras,
			[
				'CATEGORY_ID' => $this->counterPartyCategories[\CCrmOwnerType::Contact],
			]
		)
	);
	$stdItems[$counterPartyContactMenuId] = [
		'ID' => $counterPartyContactMenuId,
		'MENU_ID' => ControlPanelMenuMapper::MENU_ID_CRM_SIGN_COUNTERPARTY_CONTACTS,
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CONTACT'),
		'TITLE' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CONTACT'),
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'URL' => SITE_DIR."sign/contact/",
		'ON_CLICK' => 'event.preventDefault();BX.SidePanel.Instance.open("/sign/contact/", {cacheable: false, customLeftBoundary: 0,});',
	];
}

$stdItems['SETTINGS'] = array(
	'ID' => 'SETTINGS',
	'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('SETTINGS'), //'menu_crm_configs',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CONFIGS'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CONFIGS'),
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SETTINGS']),
	'ICON' => 'settings'
);

if ($isAdmin || $userPermissionsService->isCrmAdmin())
{
	$stdItems['MY_COMPANY'] = [
		'ID' => 'MY_COMPANY',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_COMPANY'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MY_COMPANY']),
	];

	$stdItems['PERMISSIONS'] = [
		'ID' => 'PERMISSIONS',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_PERMISSIONS'),
	];

	$crmPermsViewEventBuilder = (new Crm\Integration\Analytics\Builder\Security\ViewEvent())
		->setSection(
			!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
				? $arParams['ANALYTICS']['c_section']
				: null
		)
		->setSubSection(\Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_CONTROL_PANEL)
	;

	$stdItems['CRM_PERMISSIONS'] = [
		'ID' => 'CRM_PERMISSIONS',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CRM_PERMISSIONS_MSGVER_1'),
		'URL' => (string)$crmPermsViewEventBuilder->buildUri($router->getPermissionsUrl()),
	];
}

if (Loader::includeModule('catalog'))
{
	$catalogRights = null;
	if (Catalog\Config\Feature::isAccessControllerCheckingEnabled())
	{
		$catalogRightsUrl = '/shop/settings/permissions/';
		$catalogRights = [
			'ID' => 'CATALOG_PERMISSIONS',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOG_PERMISSIONS_MSGVER_1'),
			'URL' => $catalogRightsUrl,
			'ON_CLICK' => 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($catalogRightsUrl) . '"); return false;'
		];
	}
	else
	{
		$helpLink = Catalog\Config\Feature::getAccessControllerHelpLink();
		if (!empty($helpLink))
		{
			$catalogRights = [
				'ID' => 'CATALOG_PERMISSIONS',
				'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOG_PERMISSIONS_MSGVER_1'),
				'URL' => '',
				'ON_CLICK' => $helpLink['LINK'],
				'IS_LOCKED' => true,
			];
		}
	}

	if ($catalogRights)
	{
		$stdItems['PERMISSIONS'] ??= [
			'ID' => 'PERMISSIONS',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_PERMISSIONS'),
		];

		$stdItems['CATALOG_PERMISSIONS'] = $catalogRights;

		if (Manager::isOnecMode())
		{
			$storeMenuCatalogRights = $catalogRights;
			$storeMenuCatalogRights['ID'] = 'STORE_MENU_CATALOG_PERMISSIONS';
			$storeMenuCatalogRights['NAME'] = Loc::getMessage('CRM_CTRL_PANEL_ITEM_STORE_MENU_CATALOG_PERMISSIONS');
			$stdItems['STORE_MENU_CATALOG_PERMISSIONS'] = $storeMenuCatalogRights;
		}
	}

	if (Manager::isOnecMode())
	{
		\Bitrix\Main\UI\Extension::load(['catalog.config.settings']);

		$stdItems['CATALOG_SETTINGS'] = [
			'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CATALOG_SETTINGS'),
			'SORT' => 60,
			'ID' => 'CATALOG_SETTINGS',
			'PARENT_ID' => '',
			'ON_CLICK' => 'BX.Catalog.Config.Slider.open("crm");',
		];
	}
}

if($isAdmin || $userPermissionsService->entityType()->canReadItems(CCrmOwnerType::Quote))
{
	$actions = array();
	if($isAdmin || $userPermissionsService->entityType()->canAddItems(CCrmOwnerType::Quote))
	{
		if($isSliderEnabled)
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_QUOTE_DETAILS'],
				array('quote_id' => 0)
			);
		}
		else
		{
			$createUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_QUOTE_EDIT'],
				array('quote_id' => 0)
			);
		}

		$actions[] = [
			'ID' => 'CREATE',
			'URL' =>
				$analyticsEventBuilder
					->setEntityTypeId(\CCrmOwnerType::Quote)
					->buildUri($createUrl)
					->getUri()
			,
		];
	}


	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Quote,
		EntityCounterType::ALL,
		$currentUserID,
		$counterExtras
	);


	$stdItems['QUOTE'] = array(
		'ID' => 'QUOTE',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId(CCrmOwnerType::Quote),
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE_MSGVER_1'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE_TITLE_MSGVER_1'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_QUOTE_INDEX']) && $arParams['PATH_TO_QUOTE_INDEX'] !== ''
				? $arParams['PATH_TO_QUOTE_INDEX'] : $arParams['PATH_TO_QUOTE_LIST']
		),
		'ICON' => 'quote',
		'COUNTER' => $counter->getValue(),
		'COUNTER_ID' => $counter->getCode(),
		'ACTIONS' => $actions,
		'IS_DISABLED' => true
	);

	if (!RestrictionManager::getQuotesRestriction()->hasPermission())
	{
		unset($stdItems['QUOTE']['URL']);
		$stdItems['QUOTE']['IS_LOCKED'] = true;
		$stdItems['QUOTE']['ON_CLICK'] = RestrictionManager::getQuotesRestriction()->prepareFeaturePromoterScript();
	}
}

$stdItems['RECYCLE_BIN'] = array(
	'ID' => 'RECYCLE_BIN',
	'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('RECYCLE_BIN'), // 'menu_crm_recycle_bin',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_RECYCLE_BIN'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_RECYCLE_BIN_TITLE'),
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_RECYCLE_BIN']),
	'ICON' => 'recycle_bin',
	'IS_DISABLED' => true
);

if($isAdmin || $userPermissionsService->entityType()->canReadItems(CCrmOwnerType::Deal))
{
	$stdItems['DEAL_FUNNEL'] = array(
		'ID' => 'DEAL_FUNNEL',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('DEAL_FUNNEL'), // 'menu_crm_funel',
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
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('REPORT'), // 'menu_crm_report',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT_CONSTRUCTOR2'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REPORT_LIST']),
		'ICON' => 'report',
		'IS_DISABLED' => true
	);
	if (!RestrictionManager::getReportRestriction()->hasPermission())
	{
		unset($stdItems['REPORT']['URL']);
		$stdItems['REPORT']['IS_LOCKED'] = true;
		$stdItems['REPORT']['ON_CLICK'] = RestrictionManager::getReportRestriction()->prepareInfoHelperScript();
	}
}

$eventItem = array(
	'ID' => 'EVENT',
	'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('EVENT'), // 'menu_crm_event',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'), //title
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_EVENT_LIST']),
	'ICON' => 'event',
	'IS_DISABLED' => true
);
if (!RestrictionManager::isHistoryViewPermitted())
{
	unset($eventItem['URL']);
	$eventItem['IS_LOCKED'] = true;
	$eventItem['ON_CLICK'] = RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript();
}

$stdItems['EVENT'] = $eventItem;
unset($eventItem);

if($isAdmin || $userPermissionsService->webForm()->canRead())
{
	$stdItems['WEBFORM'] = array(
		'ID' => 'WEBFORM',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('WEBFORM'), // 'menu_crm_webform',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_WEBFORM'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_WEBFORM'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_WEBFORM']),
		'ICON' => 'webform',
		'IS_DISABLED' => true
	);
}

if($isAdmin || $userPermissionsService->siteButton()->canRead())
{
	$stdItems['SITEBUTTON'] = array(
		'ID' => 'SITEBUTTON',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('SITEBUTTON'), // 'menu_crm_button',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_BUTTON'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_BUTTON'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_BUTTON']),
		'ICON' => 'sitebutton',
		'IS_DISABLED' => true
	);
}

if ($this->isOldPortal())
{
	$stdItems['START'] = array(
		'ID' => 'START',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('START'), // 'menu_crm_start',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_START'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_START_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_START']),
		'IS_DISABLED' => true
	);
}

$stdItems['MY_ACTIVITY'] = array(
	'ID' => 'MY_ACTIVITY',
	'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('MY_ACTIVITY'), // 'menu_crm_activity',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY_MSGVER_1'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY_TITLE'),
	'URL' => CComponentEngine::MakePathFromTemplate(
		isset($arParams['PATH_TO_ACTIVITY_INDEX']) && $arParams['PATH_TO_ACTIVITY_INDEX'] !== ''
			? $arParams['PATH_TO_ACTIVITY_INDEX'] : $arParams['PATH_TO_ACTIVITY_LIST']
	),
	'ICON' => 'activity',
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
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('MARKETPLACE'), // 'menu_crm_marketplace',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MARKETPLACE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_MARKETPLACE']),
		'ICON' => 'apps',
		'IS_DISABLED' => true
	);

	if (Loader::includeModule('rest'))
	{
		$stdItems['MARKETPLACE_CRM_MIGRATION'] = array(
			'ID' => 'MARKETPLACE_CRM_MIGRATION',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MARKETPLACE_CRM_MIGRATION'),
			'URL' => Loader::includeModule('market')
				? Router::getBasePath() . 'collection/migration_crm/'
				: Router::getCategoryPath('migration'),
			'IS_DISABLED' => true,
			'SLIDER_ONLY' => true,
		);

		$stdItems['MARKETPLACE_CRM_SOLUTIONS'] = array(
			'ID' => 'MARKETPLACE_CRM_SOLUTIONS',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MARKETPLACE_CRM_SOLUTIONS'),
			'URL' => \Bitrix\Rest\Marketplace\Url::getConfigurationSectionUrl('vertical_crm'),
			'IS_DISABLED' => true,
			'SLIDER_ONLY' => true,
		);
	}
}

if (Loader::includeModule('salescenter'))
{
	if (SaleManager::getInstance()->isManagerAccess() || !SalesCenter\Driver::getInstance()->isEnabled())
	{
		$stdItems['SALES_CENTER'] = [
			'ID' => 'SALES_CENTER',
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SALES_CENTER'),
			'URL' => '/saleshub/',
			'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('SALES_CENTER'), // 'menu-sale-center',
		];
	}

	if (SaleManager::getInstance()->isManagerAccess(true) && SalesCenter\Driver::getInstance()->hasDeliveryServices())
	{
		$deliveryPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$deliveryPath = getLocalPath('components'.$deliveryPath.'/slider.php');
		$deliveryPath = new \Bitrix\Main\Web\Uri($deliveryPath);
		$deliveryPath->addParams([
			'analyticsLabel' => 'salescenterClickDeliveryTile',
			'type' => 'main',
			'mode' => 'main'
		]);
		$stdItems['SALES_CENTER_DELIVERY'] = [
			'ID' => 'SALES_CENTER_DELIVERY',
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SALES_CENTER_DELIVERY_MSGVER_1'),
			'URL' => $deliveryPath,
			'SLIDER_ONLY' => true,
		];
	}

	$paymentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.crmstore');
	$paymentPath = getLocalPath('components'.$paymentPath.'/slider.php');
	$stdItems['SALES_CENTER_PAYMENT'] = [
		'ID' => 'SALES_CENTER_PAYMENT',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SALES_CENTER_PAYMENT_MSGVER_1'),
		'URL' => $paymentPath,
		'SLIDER_ONLY' => true,
	];
}

if (Crm\Terminal\AvailabilityManager::getInstance()->isAvailable())
{
	$stdItems['TERMINAL'] = [
		'ID' => 'TERMINAL',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_TERMINAL'),
		'URL' => '/crm/terminal/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('TERMINAL'), // 'menu_terminal',
	];
}

if (
	$userPermissionsService->copilotCallAssessment()->canRead()
	&& !Crm\Copilot\CallAssessment\FillPreliminaryCallAssessments::isWaiting()
	&& AIManager::isAvailable()
)
{
	$deadline = new \Datetime('2025-04-01 00:00:00'); //todo remove after deadline date
	$isCrmCopilotEnabled = Bitrix24Manager::isFeatureEnabled(AIManager::AI_COPILOT_FEATURE_NAME);
	$callAssessmentMenuItem = [
		'ID' => 'CALL_ASSESSMENT',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CALL_ASSESSMENT'),
		'URL' => '/crm/copilot-call-assessment/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('CALL_ASSESSMENT'),
		'IS_NEW' => ($deadline > (new \Datetime())),  //todo remove after deadline date
	];
	if (!$isCrmCopilotEnabled)
	{
		unset($callAssessmentMenuItem['URL']);
		$callAssessmentMenuItem['IS_LOCKED'] = true;
		$callAssessmentMenuItem['ON_CLICK'] = Bitrix24Manager::prepareLicenseInfoHelperScript(
			[
				'ID' => AIManager::AI_COPILOT_FEATURE_RESTRICTED_SLIDER_CODE,
			]
		);
	}

	$stdItems['CALL_ASSESSMENT'] = $callAssessmentMenuItem;
}

$repeatSaleAvailabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
if (
	$repeatSaleAvailabilityChecker->isAvailable()
	&& Container::getInstance()->getUserPermissions()->repeatSale()->canRead()
)
{
	Container::getInstance()->getLocalization()->loadMessages();

	$stdItems['REPEAT_SALE_SEGMENT'] = [
		'ID' => 'REPEAT_SALE_SEGMENT',
		'NAME' => Loc::getMessage('CRM_COMMON_REPEAT_SALE'),
		'URL' => '/crm/repeat-sale-segment/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('REPEAT_SALE_SEGMENT'),
		'IS_NEW' => true, // @todo set end datetime
	];

	if (!$repeatSaleAvailabilityChecker->hasPermission())
	{
		unset($stdItems['REPEAT_SALE_SEGMENT']['URL']);
		$stdItems['REPEAT_SALE_SEGMENT']['IS_LOCKED'] = true;
		$stdItems['REPEAT_SALE_SEGMENT']['ON_CLICK'] = RestrictionManager::getRepeatSaleRestriction()->prepareFeaturePromoterScript();
	}
}

if (Loader::includeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::isMainMenuEnabled())
{
	$stdItems['TELEPHONY'] = [
		'ID' => 'TELEPHONY',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_TELEPHONY'),
		'URL' => '/telephony/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('TELEPHONY'), // 'menu_telephony',
	];
}

$stdItems['CONTACT_CENTER'] = [
	'ID' => 'CONTACT_CENTER',
	'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CONTACT_CENTER'),
	'URL' => Container::getInstance()->getRouter()->getContactCenterUrl(),
	'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('CONTACT_CENTER'), // 'menu_contact_center',
];

if (ModuleManager::isModuleInstalled('rest'))
{
	$stdItems['DEVOPS'] = [
		'ID' => 'DEVOPS',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_DEVOPS'),
		'URL' => '/devops/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('DEVOPS'), // 'menu_devops',
	];
}

if (\Bitrix\Crm\Tracking\Manager::isAccessible())
{
	$stdItems['CRM_TRACKING'] = [
		'ID' => 'CRM_TRACKING',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CRM_TRACKING'),
		'URL' => '/crm/tracking/',
		'MENU_ID' => ControlPanelMenuMapper::getCrmTabMenuIdById('CRM_TRACKING'), // 'menu_crm_tracking',
	];
}

if (Loader::includeModule('report') && \Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	\Bitrix\Main\UI\Extension::load('report.js.analytics');

	if (ModuleManager::isModuleInstalled('sale'))
	{
		$stdItems['ANALYTICS_SALES_FUNNEL'] = [
			'ID' => 'ANALYTICS_SALES_FUNNEL',
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_SALES_FUNNEL'),
			'URL' => '/report/analytics/?analyticBoardKey=crm_sales_funnel',
		];
	}

	$stdItems['ANALYTICS_MANAGERS'] = [
		'ID' => 'ANALYTICS_MANAGERS',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_MANAGERS'),
		'URL' => '/report/analytics/?analyticBoardKey=crm_managers_rating',
	];

	if (ModuleManager::isModuleInstalled('voximplant'))
	{
		$stdItems['ANALYTICS_CALLS'] = [
			'ID' => 'ANALYTICS_CALLS',
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_CALLS'),
			'URL' => '/report/telephony/?analyticBoardKey=telephony_calls_dynamics',
		];
	}

	if (ModuleManager::isModuleInstalled('imopenlines'))
	{
		$stdItems['ANALYTICS_DIALOGS'] = [
			'ID' => 'ANALYTICS_DIALOGS',
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_DIALOGS'),
			'URL' => Container::getInstance()->getRouter()->getContactCenterUrl() . 'dialog_statistics/',
		];
	}

	if (Loader::includeModule('biconnector'))
	{
		$stdItems['ANALYTICS_BI']['ID'] = 'ANALYTICS_BI';
		$stdItems['ANALYTICS_BI']['NAME'] = Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS_BI');

		if (Loader::includeModule('bitrix24') && !\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
		{
			$stdItems['ANALYTICS_BI']['IS_LOCKED'] = true;
			$stdItems['ANALYTICS_BI']['ON_CLICK'] = 'top.BX.UI.InfoHelper.show("limit_crm_BI_analytics")';
		}
		else
		{
			$bi = \Bitrix\BIConnector\Manager::getInstance();

			if (method_exists($bi, 'getMenuItems'))
			{
				$items = $bi->getMenuItems();

				if (!empty($items))
				{
					$stdItems['ANALYTICS_BI']['URL'] = '/report/analytics/?analyticBoardKey=' . $items[0]['id'];
				}
				else
				{
					unset($stdItems['ANALYTICS_BI']);
				}
			}
		}
	}
}

if (Loader::includeModule('intranet') && CIntranetUtils::IsExternalMailAvailable())
{
	$stdItems['MAIL'] = [
		'ID' => 'MAIL',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_MAIL'),
		'URL' => '/mail/',
	];
}

$stdItems['MESSENGERS'] = [
	'ID' => 'MESSENGERS',
	'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_MESSENGERS'),
	'URL' => Container::getInstance()->getRouter()->getContactCenterUrl(),
];


$allowedLangs = ['ru', 'kz', 'by', 'ua'];
$show1cSection = Loader::includeModule('bitrix24') && in_array(CBitrix24::getLicensePrefix(), $allowedLangs);
if (!$show1cSection && !ModuleManager::isModuleInstalled('bitrix24'))
{
	$show1cSection =
		file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . 'onec/') && in_array(LANGUAGE_ID, $allowedLangs)
	;
}

if ($show1cSection)
{
	$stdItems['ONEC'] = [
		'ID' => 'ONEC',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ONEC_MSGVER_1'),
		'URL' => '/onec/',
	];
}

$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
$dynamicSubItems = [];

$dynamicTypesMap->load([
	'isLoadStages' => false,
	'isLoadCategories' => true,
]);

foreach($dynamicTypesMap->getTypes() as $type)
{
	if (Crm\Integration\IntranetManager::isEntityTypeInCustomSection($type->getEntityTypeId()))
	{
		continue;
	}

	$actions = [];
	$isCanAdd = $isAdmin;
	$isAddRestricted = Crm\Restriction\RestrictionManager::getDynamicTypesLimitRestriction()->isCreateItemRestricted($type->getEntityTypeId());
	if (!$isAddRestricted)
	{
		if (!$isCanAdd)
		{
			$defaultCategory = $dynamicTypesMap->getDefaultCategory($type->getEntityTypeId());
			if ($defaultCategory)
			{
				$isCanAdd = $userPermissionsService->entityType()->canAddItemsInCategory(
					$type->getEntityTypeId(),
					$defaultCategory->getId()
				);
			}
		}
		if ($isCanAdd)
		{
			$actions[] = [
				'ID' => 'CREATE',
				'URL' => Container::getInstance()->getRouter()->getItemDetailUrl(
					$type->getEntityTypeId(),
					0
				),
			];
		}
	}
	if ($userPermissionsService->entityType()->canReadItems($type->getEntityTypeId()))
	{
		$id = CCrmOwnerType::ResolveName($type->getEntityTypeId());
		$stdItems[$id] = [
			'ID' => $id,
			'NAME' => $type->getTitle(),
			'URL' => Container::getInstance()->getRouter()->getItemListUrlInCurrentView($type->getEntityTypeId()),
			'ACTIONS' => !empty($actions) ? $actions : null,
		];

		$factory = Container::getInstance()->getFactory($type->getEntityTypeId());

		if($factory->getCountersSettings()->isCounterTypeEnabled(EntityCounterType::ALL)) {
			$counter = EntityCounterFactory::create(
				$type->getEntityTypeId(),
				EntityCounterType::ALL,
				$currentUserID,
				$counterExtras
			);
			$stdItems[$id]['COUNTER'] = $counter->getValue();
			$stdItems[$id]['COUNTER_ID'] = $counter->getCode();
		}

		$dynamicSubItems[] = ['ID' => $id];
	}
}

$defaultDynamicItems = [];
if ($userPermissionsService->isCrmAdmin())
{
	if (!empty($dynamicSubItems))
	{
		$defaultDynamicItems[] = [
			'IS_DELIMITER' => true,
		];
	}

	$defaultDynamicItems[] = [
		'ID' => 'DYNAMIC_LIST',
	];

	$stdItems['DYNAMIC_LIST'] = [
		'ID' => 'DYNAMIC_LIST',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SMART_ENTITY_LIST_MSGVER_1'),
		'URL' => Container::getInstance()->getRouter()->getTypeListUrl(),
	];
}

$dynamicStdSubItems = array_merge($dynamicSubItems, $defaultDynamicItems);
if (empty($dynamicStdSubItems))
{
	$stdItems['DYNAMIC_ITEMS'] = [];
}
else
{
	$stdItems['DYNAMIC_ITEMS'] = [
		'ID' => 'DYNAMIC_ITEMS',
		'MENU_ID' => 'menu_dynamic_list',
		'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_DYNAMIC_LIST'),
		'SUB_ITEMS' => $dynamicStdSubItems,
	];
}

if (Bitrix\Crm\Feature::enabled(\Bitrix\Crm\Feature\ShowLinkToFeaturesInMenu::class))
{
	$stdItems['FEATURES_LIST'] = [
		'ID' => 'FEATURES_LIST',
		'NAME' => \Bitrix\Crm\Feature\ShowLinkToFeaturesInMenu::getMenuTitle(),
		'URL' => '/crm/configs/?expert',
	];
}

// <-- Prepere standard items

$items = array();
$itemInfos = isset($arParams['ITEMS']) && is_array($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
if(empty($itemInfos))
{
	$items = $this->createMenuTree($stdItems);
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

$items = array_merge(
	$items,
	$this->getAvailableCategoriesMenuItems(CCrmOwnerType::Contact),
	$this->getAvailableCategoriesMenuItems(CCrmOwnerType::Company)
);

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

if ($isMenuMode)
{
	$arResult['ITEMS'] = $this->createFileMenuItems($items);

	return $arResult;
}
else
{
	$arResult['ITEMS'] = $this->prepareItems($items);
	unset($items);

	if ($isGetResult)
	{
		return $arResult;
	}

	$this->IncludeComponentTemplate();
}
