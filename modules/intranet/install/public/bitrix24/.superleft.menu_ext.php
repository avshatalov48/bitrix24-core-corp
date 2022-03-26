<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Site\Sections\AutomationSection;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use \Bitrix\Landing\Rights;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

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

$arMenu = [
	[
		GetMessage("MENU_LIVE_FEED2"),
		"/stream/",
		[],
		[
			"name" => "live_feed",
			"counter_id" => "live-feed",
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
			"sub_link" => SITE_DIR."company/personal/user/".$userId."/tasks/task/edit/0/",
			"top_menu_id" => "tasks_panel_menu",
		],
		""
	]
];

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
		array("/crm/"),
		array(
			"real_link" => \Bitrix\Crm\Settings\EntityViewSettings::getDefaultPageUrl(),
			"counter_id" => $counterId,
			"menu_item_id" => "menu_crm_favorite",
			"top_menu_id" => "crm_control_panel_menu"
		),
		""
	);
}
// NEW MENU
// else
// {
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
// }

// OLD MENU
if (CModule::IncludeModule('crm') && \Bitrix\Crm\Tracking\Manager::isAccessible())
{
	$arMenu[] = [
		Loc::getMessage('MENU_CRM_TRACKING'),
		'/crm/tracking/',
		[],
		[
			'menu_item_id' => 'menu_crm_tracking',
		],
		''
	];
}

// OLD MENU
if (Loader::includeModule('landing'))
{
	if (Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24']))
	{
		$arMenu[] = [
			GetMessage('MENU_SITES'),
			'/sites/',
			[],
			[
				'menu_item_id' => 'menu_sites',
			],
			''
		];
	}

	if (Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24'], 'knowledge'))
	{
		$arMenu[] = [
			GetMessage('MENU_KNOWLEDGE'),
			'/kb/',
			[],
			[
				'menu_item_id' => 'menu_knowledge',
			],
			''
		];
	}
}

/*
NEW MENU
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
				'/shop/orders/menu/'
			),
			'menu_item_id' => 'menu_shop',
			'top_menu_id' => 'store',
			'counter_id' => CCrmSaleHelper::isWithOrdersMode() ? 'shop_all' : '',
		],
		''
	];
}
else if (Loader::includeModule('landing') && Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24']))
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
}*/

// OLD MENU
if (Loader::includeModule('crm') && CCrmSaleHelper::isShopAccess())
{
	if (Loader::includeModule('salescenter') && \Bitrix\SalesCenter\Driver::getInstance()->isEnabled())
	{
		$arMenu[] = [
			Loc::getMessage('MENU_SALESCENTER_SECTION'),
			'/saleshub/',
			[],
			[
				'real_link' => getLeftMenuItemLink(
					'top_menu_id_saleshub',
					'/saleshub/'
				),
				'menu_item_id' => 'menu-sale-center',
				'top_menu_id' => 'top_menu_id_saleshub',
				'is_beta' => true,
			],
			''
		];
	}

	$arMenu[] = [
		Loc::getMessage('MENU_SHOP'),
		'/shop/menu/',
		[
			'/shop/',
		],
		[
			'real_link' => getLeftMenuItemLink(
				'store',
				'/shop/orders/menu/'
			),
			'menu_item_id' => 'menu_shop',
			'top_menu_id' => 'store',
			'is_beta' => true,
			'counter_id' => CCrmSaleHelper::isWithOrdersMode() ? 'shop_all' : '',
		],
		''
	];
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

// OLD MENU
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
		"menu_item_id"=>"menu_all_groups",
		"top_menu_id" => "sonetgroups_panel_menu"
	],
	""
];

// OLD MENU
if (Loader::includeModule('rpa') && \Bitrix\Rpa\Driver::getInstance()->isEnabled())
{
	$arMenu[] = [
		Loc::getMessage('MENU_RPA_SECTION'),
		'/rpa/',
		[],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_rpa',
				'/rpa/'
			),
			'counter_id' => 'rpa_tasks',
			'menu_item_id' => 'menu_rpa',
			'top_menu_id' => 'top_menu_id_rpa',
			'is_beta' => true,
		],
		''
	];
}

// OLD MENU
if (Loader::includeModule('bizproc') && CBPRuntime::isFeatureEnabled())
{
	$arMenu[] = [
		Loc::getMessage('MENU_BIZPROC'),
		'/bizproc/',
		[
			'/company/personal/bizproc/',
			'/company/personal/processes/',
		],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_bizproc',
				'/company/personal/bizproc/'
			),
			'counter_id' => 'bp_tasks',
			'menu_item_id' => 'menu_bizproc_sect',
			'top_menu_id' => 'top_menu_id_bizproc',
		],
		''
	];
}

/*
NEW MENU
if (Loader::includeModule('intranet') && AutomationSection::isAvailable())
{
	$arMenu[] = AutomationSection::getRootMenuItem();
}
*/

// OLD MENU
if (Loader::includeModule('intranet'))
{
	$items = [
		AutomationSection::getAI(),
		AutomationSection::getOnec(),
	];

	foreach ($items as $item)
	{
		if ($item['available'])
		{
			$arMenu[] = [
				$item['title'] ?? '',
				$item['url'] ?? '',
				$item['extraUrls'] ?? [],
				$item['menuData'] ?? [],
				'',
			];
		}
	}
}

//marketplace
$arMenu[] = array(
	GetMessage("MENU_MARKETPLACE_APPS_2"),
	"/marketplace/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_marketplace",
			"/marketplace/"
		),
		"class" => "menu-apps",
		"menu_item_id" => "menu_marketplace_sect",
		"top_menu_id" => "top_menu_id_marketplace"
	),
	""
);

// OLD MENU
if (Loader::includeModule('report') && \Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	\Bitrix\Main\UI\Extension::load('report.js.analytics');

	$arMenu[] = [
		Loc::getMessage('MENU_REPORT_ANALYTICS'),
		'/report/analytics/',
		[],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_analytics',
				'/report/analytics/'
			),
			'menu_item_id' => 'menu_analytics',
			'top_menu_id' => 'top_menu_id_analytics',
		],
		''
	];
}

$arMenu[] = [
	GetMessage('MENU_COMPANY_SECTION'),
	'/company/',
	[
		/*
		NEW MENU
		'/timeman/',
		'/kb/',
		'/conference/',
		*/
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

// OLD MENU
$arMenu[] = [
	Loc::getMessage('MENU_TIMEMAN_SECTION'),
	'/timeman/',
	[],
	[
		'real_link' => getLeftMenuItemLink(
			'top_menu_id_timeman',
			'/timeman/'
		),
		'menu_item_id'=>'menu_timeman_sect',
		'top_menu_id' => 'top_menu_id_timeman'
	],
	''
];

// OLD MENU
if (Loader::includeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::isMainMenuEnabled())
{
	$arMenu[] = [
		Loc::getMessage('MENU_TELEPHONY_SECTION'),
		'/telephony/',
		[],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_telephony',
				'/telephony/'
			),
			'class' => 'menu-telephony',
			'menu_item_id' => 'menu_telephony',
			'top_menu_id' => 'top_menu_id_telephony'
		],
		''
	];
}

// OLD MENU
if (Loader::includeModule('im'))
{
	$arMenu[] = [
		Loc::getMessage('MENU_CONFERENCE_SECTION'),
		'/conference/',
		[],
		[
			'class' => 'menu-conference',
			'menu_item_id' => 'menu_conference',
			'top_menu_id' => 'top_menu_id_conference',
		],
		''
	];
}

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
		"/settings/configs/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_settings_configs",
				"/settings/configs/"
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
