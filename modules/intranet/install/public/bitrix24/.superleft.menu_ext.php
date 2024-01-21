<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Intranet\Site\Sections\AutomationSection;
use \Bitrix\Landing\Rights;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Intranet\Settings\Tools\ToolsManager;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/.superleft.menu_ext.php");
CModule::IncludeModule("intranet");

if (!function_exists("getLeftMenuItemLink"))
{
	function getLeftMenuItemLink($sectionId, $defaultLink = "")
	{
		$settings = CUserOptions::GetOption("UI", $sectionId);
		return
			is_array($settings) && isset($settings["firstPageLink"]) && mb_strlen($settings["firstPageLink"]) ?
				$settings["firstPageLink"] :
				$defaultLink;
	}
}

$userId = $GLOBALS["USER"]->GetID();

if (defined("BX_COMP_MANAGED_CACHE"))
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->registerTag("bitrix24_left_menu");
	$CACHE_MANAGER->registerTag("crm_change_role");
	$CACHE_MANAGER->registerTag("USER_NAME_".$userId);
}

$isNewLiveFeedCounterAvailable = (
	Loader::includeModule('socialnetwork')
	&& \Bitrix\Socialnetwork\Space\Service::isAvailable()
	&& \Bitrix\Socialnetwork\Internals\LiveFeed\Counter\CounterController::isEnabled((int)$userId)
);

$arMenu = [
	[
		GetMessage("MENU_LIVE_FEED2"),
		"/stream/",
		[],
		[
			"name" => "live_feed",
			"counter_id" => $isNewLiveFeedCounterAvailable ? 'sonet_total' : 'live-feed',
			"menu_item_id" => "menu_live_feed",
		],
		""
	],
	[
		GetMessage("MENU_TASKS"),
		"/tasks/menu/",
		[],
		[
			"real_link" => getLeftMenuItemLink(
				"tasks_panel_menu",
				"/company/personal/user/".$userId."/tasks/"
			),
			"name" => "tasks",
			"counter_id" => "tasks_total",
			"menu_item_id" => "menu_tasks",
			"sub_link" => SITE_DIR."company/personal/user/".$userId."/tasks/task/edit/0/?ta_sec=left_menu&ta_el=create_button",
			"top_menu_id" => "tasks_panel_menu",
		],
		""
	]
];

if (
	Loader::includeModule('catalog')
	&& AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
	&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
)
{
	$arMenu[] = array(
		GetMessage("MENU_STORE_ACCOUNTING_SECTION"),
		'/shop/documents/inventory/',
		[
			'/shop/documents/',
			'/shop/documents-catalog/',
			'/shop/documents-stores/',
		],
		['menu_item_id' => 'menu_crm_store'],
		''
	);
}

$arMenu[] = array(
	GetMessage("MENU_CALENDAR"),
	"/calendar/",
	array(
		"/company/personal/user/".$userId."/calendar/",
	),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_calendar",
			"/company/personal/user/".$userId."/calendar/"
		),
		"menu_item_id" => "menu_calendar",
		"sub_link" => SITE_DIR."company/personal/user/".$userId."/calendar/?EVENT_ID=NEW",
		"counter_id" => "calendar",
		"top_menu_id" => "top_menu_id_calendar",
	),
	""
);

$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
$diskPath =
	$diskEnabled === "Y" ?
		"/company/personal/user/".$userId."/disk/path/" :
		"/company/personal/user/".$userId."/files/lib/"
;

$arMenu[] = array(
	GetMessage("MENU_DISK_SECTION"),
	"/docs/",
	array(
		$diskPath,
		"/company/personal/user/".$userId."/disk/volume/",
		"/company/personal/user/".$userId."/disk/"
	),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_docs",
			$diskPath
		),
		"menu_item_id" => "menu_files",
		"top_menu_id" => "top_menu_id_docs",
	),
	""
);
if ($diskEnabled === "Y" && \Bitrix\Main\Config\Option::get('disk', 'documents_enabled', 'N') === 'Y')
{
	$arMenu[] = array(
		GetMessage("MENU_DISK_DOCUMENTS"),
		"/company/personal/user/".$userId."/disk/documents/",
		[],
		array(
			"menu_item_id" => "menu_documents",
		),
		""
	);
}

if (CModule::IncludeModule("crm") && CCrmPerms::IsAccessEnabled())
{
	$counterId = CCrmSaleHelper::isWithOrdersMode() ? 'crm_all' : 'crm_all_no_orders';
	$arMenu[] = array(
		GetMessage("MENU_CRM"),
		"/crm/menu/",
		[
			"/crm/",
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') ? '/contact_center/' : SITE_DIR . 'services/contact_center/',
			'/bi/dashboard/',
		],
		[
			"real_link" => \Bitrix\Crm\Settings\EntityViewSettings::getDefaultPageUrl(),
			"counter_id" => $counterId,
			"menu_item_id" => "menu_crm_favorite",
			"top_menu_id" => "crm_control_panel_menu",
		],
		""
	);
}
else
{
	$arMenu[] = [
		Loc::getMessage('MENU_CONTACT_CENTER'),
		'/contact_center/',
		[],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_contact_center',
				'/contact_center/'
			),
			'menu_item_id' => 'menu_contact_center',
			'top_menu_id' => 'top_menu_id_contact_center',
		],
		'',
	];
}

if (ToolsManager::getInstance()->checkAvailabilityByMenuId('menu_shop'))
{
	$landingAvailable = Loader::includeModule('landing') && Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24']);

	if (Loader::includeModule('crm') && CCrmSaleHelper::isShopAccess())
	{
		$arMenu[] = [
			GetMessage('MENU_SITES_AND_STORES'),
			'/shop/menu/',
			[
				'/shop/',
				'/sites/'
			],
			[
				'real_link' => getLeftMenuItemLink(
					'store',
					$landingAvailable ? '/sites/' : '/shop/orders/menu/'
				),
				'menu_item_id' => 'menu_shop',
				'top_menu_id' => 'store',
				'counter_id' => CCrmSaleHelper::isWithOrdersMode() ? 'shop_all' : '',
			],
			''
		];
	}
	elseif ($landingAvailable)
	{
		$arMenu[] = [
			Loc::getMessage('MENU_SITES'),
			'/sites/',
			[],
			[
				'menu_item_id' => 'menu_sites',
			],
			''
		];
	}
}

if (CModule::IncludeModule("sender") && \Bitrix\Sender\Security\User::current()->hasAccess())
{
	$arMenu[] = array(
		GetMessage("MENU_MARKETING"),
		"/marketing/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_marketing",
				"/marketing/"
			),
			"menu_item_id" => "menu_marketing",
			'top_menu_id' => 'top_menu_id_marketing',
		),
		""
	);
}

$arMenu[] = [
	Loc::getMessage('MENU_IM_MESSENGER'),
	'/online/',
	[],
	[
		'counter_id' => 'im-message',
		'menu_item_id' => 'menu_im_messenger',
		'my_tools_section' => true,
		'can_be_first_item' => false
	],
	''
];

if (Loader::includeModule('sign') && \Bitrix\Sign\Config\Storage::instance()->isAvailable())
{
	$arMenu[] = [
		Loc::getMessage('MENU_SIGN'),
		'/sign/',
		[],
		[
			'menu_item_id' => 'menu_sign',
			'my_tools_section' => true,
			'can_be_first_item' => true,
		],
		''
	];
}

if (CModule::IncludeModule("intranet") && CIntranetUtils::IsExternalMailAvailable())
{
	$warningLink = $mailLink = \Bitrix\Main\Config\Option::get('intranet', 'path_mail_client', '/mail/');

	$arMenu[] = array(
		GetMessage("MENU_MAIL"),
		$mailLink,
		array(),
		array(
			"counter_id" => "mail_unseen",
			"warning_link" => $warningLink,
			"warning_title" => GetMessage("MENU_MAIL_CHANGE_SETTINGS"),
			"menu_item_id" => "menu_external_mail",
		),
		""
	);
}

//groups
$arMenu[] = [
	GetMessage("MENU_GROUP_SECTION"),
	"/workgroups/",
	[],
	[
		"real_link" => getLeftMenuItemLink(
			"sonetgroups_panel_menu",
			"/workgroups/"
		),
		"sub_link" => "/company/personal/user/".$userId."/groups/create/",
		"menu_item_id" => "menu_all_groups",
		"top_menu_id" => "sonetgroups_panel_menu",
		// todo oh 'counter_id' => 'workgroups',
	],
	""
];

$isSpacesAvailable = (
	Loader::includeModule('socialnetwork')
	&& \Bitrix\Socialnetwork\Space\Service::isAvailable(true)
);
if ($isSpacesAvailable)
{
	$arMenu[] = [
		GetMessage('MENU_GROUP_SPACES'),
		'/spaces/',
		[],
		[
			'menu_item_id' => 'menu_all_spaces',
			'counter_id' => 'spaces',
		],
		''
	];
}

if (Loader::includeModule('intranet') && AutomationSection::isAvailable())
{
	$automationItem = AutomationSection::getRootMenuItem();
	$automationItem[3]['real_link'] = getLeftMenuItemLink(
		"top_menu_id_automation",
		!empty($automationItem[3]['first_item_url']) ? $automationItem[3]['first_item_url'] : $automationItem[1]
	);

	$arMenu[] = $automationItem;
}

//marketplace
$arMenu[] = array(
	GetMessage("MENU_MARKETPLACE_APPS_2"),
	Marketplace::getMainDirectory(),
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_marketplace",
			Marketplace::getMainDirectory(),
		),
		"class" => "menu-apps",
		"menu_item_id" => "menu_marketplace_sect",
		"top_menu_id" => "top_menu_id_marketplace"
	),
	""
);

$arMenu[] = [
	GetMessage("MENU_DEVOPS"),
	"/devops/",
	[],
	[
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_devops",
			"/devops/"
		),
		"class" => "menu-devops",
		"menu_item_id" => "menu_devops_sect",
		"top_menu_id" => "top_menu_id_devops",
	],
	"",
];

$arMenu[] = [
	GetMessage('MENU_COMPANY_SECTION'),
	'/company/',
	[
		'/timeman/',
		'/kb/',
		'/conference/',
	],
	[
		'real_link' => getLeftMenuItemLink(
			'top_menu_id_company',
			'/company/vis_structure.php'
		),
		'class' => 'menu-company',
		'menu_item_id' => 'menu_company',
		'top_menu_id' => 'top_menu_id_company',
	],
	'',
];

if (IsModuleInstalled("bitrix24"))
{
	$arMenu[] = array(
		GetMessage("MENU_TARIFF"),
		"/settings/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_settings",
				$GLOBALS['USER']->CanDoOperation('bitrix24_config') ? "/settings/license.php" : "/settings/license_all.php"
			),
			"class" => "menu-tariff",
			"menu_item_id" => "menu_tariff",
			"top_menu_id" => "top_menu_id_settings"
		),
		""
	);
}
else
{
	$arMenu[] = array(
		GetMessage("MENU_LICENSE"),
		"/updates/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_updates",
				"/updates/"
			),
			"menu_item_id" => "menu_updates",
			"top_menu_id" => "top_menu_id_updates"
		),
		""
	);
}

if (
	IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config')
	|| !IsModuleInstalled("bitrix24") && $GLOBALS['USER']->IsAdmin()
)
{
	$arMenu[] = array(
		GetMessage("MENU_SETTINGS_SECTION"),
		"/settings/configs/?analyticContext=left_menu_main",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_settings_configs",
				"/settings/configs/?analyticContext=left_menu_main"
			),
			"class" => "menu-settings",
			"menu_item_id" => "menu_configs_sect",
			"top_menu_id" => "top_menu_id_settings_configs"
		),
		""
	);
}

$pageManager = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('intranet.customSection.manager');
$pageManager->appendSuperLeftMenuSections($arMenu);

$aMenuLinks = $arMenu;
