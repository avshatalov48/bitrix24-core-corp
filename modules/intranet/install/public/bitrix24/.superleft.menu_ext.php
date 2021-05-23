<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Landing\Rights;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/.superleft.menu_ext.php");
CModule::IncludeModule("intranet");

$bLandingIncluded = \Bitrix\Main\Loader::includeModule("landing");

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

$arMenu = array(
	array(
		GetMessage("MENU_LIVE_FEED2"),
		"/stream/",
		array(),
		array(
			"name" => "live_feed",
			"counter_id" => "live-feed",
			"menu_item_id" => "menu_live_feed",
			"my_tools_section" => true,
		),
		""
	),
	array(
		GetMessage("MENU_TASKS"),
		"/company/personal/user/".$userId."/tasks/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"tasks_panel_menu",
				"/company/personal/user/".$userId."/tasks/"
			),
			"name" => "tasks",
			"counter_id" => "tasks_total",
			"menu_item_id" => "menu_tasks",
			"sub_link" => SITE_DIR."company/personal/user/".$userId."/tasks/task/edit/0/",
			"top_menu_id" => "tasks_panel_menu",
			"my_tools_section" => true,
		),
		""
	)
);

if ($bLandingIncluded)
{
	if (Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS["menu24"]))
	{
		$arMenu[] = array(
			GetMessage("MENU_SITES"),
			"/sites/",
			array(),
			array(
				"menu_item_id" => "menu_sites",
				"my_tools_section" => true
			),
			""
		);
	}
	if (Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS["menu24"], "knowledge"))
	{
		$arMenu[] = array(
			GetMessage("MENU_KNOWLEDGE"),
			"/kb/",
			array(),
			array(
				"menu_item_id" => "menu_knowledge",
				"my_tools_section" => true,
				"is_beta" => true,
			),
			""
		);
	}
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
		"my_tools_section" => true,
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
		"my_tools_section" => true,
	),
	""
);

$arMenu[] = array(
	GetMessage("MENU_PHOTO"),
	"/company/personal/user/".$userId."/photo/",
	array(),
	array(
		"menu_item_id" => "menu_photo",
		"my_tools_section" => true,
		"hidden" => true
	),
	""
);

$arMenu[] = array(
	GetMessage("MENU_BLOG"),
	"/company/personal/user/".$userId."/blog/",
	array(),
	array(
		"menu_item_id" => "menu_blog",
		"my_tools_section" => true,
		"hidden" => true
	),
	""
);

if (CModule::IncludeModule("crm") && CCrmPerms::IsAccessEnabled())
{
	$arMenu[] = array(
		GetMessage("MENU_CRM"),
		"/crm/menu/",
		array("/crm/"),
		array(
			"real_link" => \Bitrix\Crm\Settings\EntityViewSettings::getDefaultPageUrl(),
			"counter_id" => "crm_all",
			"menu_item_id" => "menu_crm_favorite",
			"top_menu_id" => "crm_control_panel_menu"
		),
		""
	);
}

if (CModule::IncludeModule("crm") && \Bitrix\Crm\Tracking\Manager::isAccessible())
{
	$arMenu[] = array(
		GetMessage("MENU_CRM_TRACKING"),
		"/crm/tracking/",
		array(),
		array(
			"menu_item_id" => "menu_crm_tracking",
		),
		""
	);
}

if (CModule::IncludeModule("crm") && CCrmSaleHelper::isShopAccess())
{
	if(\Bitrix\Main\Loader::includeModule('salescenter') && \Bitrix\SalesCenter\Driver::getInstance()->isEnabled())
	{
		$arMenu[] = array(
			GetMessage("MENU_SALESCENTER_SECTION"),
			"/saleshub/",
			array(),
			array(
				"real_link" => getLeftMenuItemLink(
					"top_menu_id_saleshub",
					"/saleshub/"
				),
				"menu_item_id" => "menu-sale-center",
				"top_menu_id" => "top_menu_id_saleshub",
				"is_beta" => true,
			),
			""
		);
	}

	$arMenu[] = array(
		GetMessage("MENU_SHOP"),
		"/shop/menu/",
		array("/shop/"),
		array(
			"real_link" => getLeftMenuItemLink(
				"store",
				"/shop/orders/menu/"
			),
			"counter_id" => "shop_all",
			"menu_item_id" => "menu_shop",
			"top_menu_id" => "store",
			"is_beta" => true
		),
		""
	);
}

if (CModule::IncludeModule("sender") && \Bitrix\Sender\Security\User::current()->hasAccess())
{
	$arMenu[] = array(
		GetMessage("MENU_CRM_MARKETING"),
		"/marketing/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_marketing",
				"/marketing/"
			),
			"menu_item_id" => "menu_marketing",
		),
		""
	);
}

$arMenu[] = array(
	GetMessage("MENU_IM_MESSENGER"),
	"/online/",
	array(),
	array(
		"counter_id" => "im-message",
		"menu_item_id" => "menu_im_messenger",
		"my_tools_section" => true,
		"can_be_first_item" => false
	),
	""
);

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
			"my_tools_section" => true,
		),
		""
	);
}

//groups
$arMenu[] = array(
	GetMessage("MENU_GROUP_SECTION"),
	"/workgroups/menu/",
	array("/workgroups/"),
	array(
		"real_link" => getLeftMenuItemLink(
			"sonetgroups_panel_menu",
			"/workgroups/"
		),
		"sub_link" => "/company/personal/user/".$userId."/groups/create/",
		"menu_item_id"=>"menu_all_groups",
		"top_menu_id" => "sonetgroups_panel_menu"
	),
	""
);

if(\Bitrix\Main\Loader::includeModule('rpa') && \Bitrix\Rpa\Driver::getInstance()->isEnabled())
{
	$arMenu[] = [
		\Bitrix\Main\Localization\Loc::getMessage("MENU_RPA_SECTION"),
		"/rpa/",
		[],
		[
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_rpa",
				"/rpa/"
			),
			"counter_id" => "rpa_tasks",
			"menu_item_id" => "menu_rpa",
			"top_menu_id" => "top_menu_id_rpa",
			"is_beta" => true,
		],
		""
	];
}

if (CModule::IncludeModule("bizproc") && CBPRuntime::isFeatureEnabled())
{
	$arMenu[] = array(
		GetMessage("MENU_BIZPROC"),
		"/bizproc/",
		array(
			"/company/personal/bizproc/",
			"/company/personal/processes/",
		),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_bizproc",
				"/company/personal/bizproc/"
			),
			"counter_id" => "bp_tasks",
			"menu_item_id" => "menu_bizproc_sect",
			"top_menu_id" => "top_menu_id_bizproc",
			"my_tools_section" => true,
		),
		""
	);
}
$licensePrefix = "";
if (CModule::IncludeModule("bitrix24"))
{
	$licensePrefix = CBitrix24::getLicensePrefix();
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

//devops
$arMenu[] = array(
	GetMessage("MENU_DEVOPS"),
	"/devops/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_devops",
			"/devops/"
		),
		"class" => "menu-devops",
		"menu_item_id" => "menu_devops_sect",
		"top_menu_id" => "top_menu_id_devops"
	),
	""
);

if (LANGUAGE_ID === "ru")
{
	$arMenu[] = [
		GetMessage("MENU_AI_SECTION"),
		"/ai/",
		[],
		[
			"menu_item_id"=>"menu_ai",
		],
		""
	];
}

if (IsModuleInstalled("bitrix24") &&  in_array($licensePrefix, array('ru', 'kz', 'by', 'ua')) || !IsModuleInstalled("bitrix24"))
{
	if(IsModuleInstalled("crm"))
	{
		$arMenu[] = array(
			GetMessage("MENU_ONEC_SECTION"),
			"/onec/",
			array(),
			array(
				"real_link" => getLeftMenuItemLink(
					"top_menu_id_onec",
					"/onec/"
				),
				"menu_item_id"=>"menu_onec_sect",
				"top_menu_id" => "top_menu_id_onec"
			),
			""
		);
	}
}

if (\Bitrix\Main\Loader::includeModule('report') && \Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	\Bitrix\Main\UI\Extension::load('report.js.analytics');
	$arMenu[] = array(
		GetMessage("MENU_REPORT_ANALYTICS"),
		"/report/analytics/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_analytics",
				"/report/analytics/"
			),
			"menu_item_id" => "menu_analytics",
			"top_menu_id" => "top_menu_id_analytics",
			"is_beta" => true
		),
		""
	);
}

$arMenu[] = array(
	GetMessage("MENU_EMPLOYEE"),
	"/company/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_company",
			"/company/vis_structure.php"
		),
		"class" => "menu-company",
		"menu_item_id" => "menu_company",
		"top_menu_id" => "top_menu_id_company"
	),
	""
);

$arMenu[] = array(
	GetMessage("MENU_TIMEMAN_SECTION"),
	"/timeman/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_timeman",
			"/timeman/"
		),
		"menu_item_id"=>"menu_timeman_sect",
		"top_menu_id" => "top_menu_id_timeman"
	),
	""
);

/*if (CModule::IncludeModule("imopenlines") && \Bitrix\ImOpenlines\Security\Helper::isMainMenuEnabled())
{
	$arMenu[] = array(
		GetMessage("MENU_OPENLINES_LINES_SINGLE"),
		"/openlines/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_openlines",
				"/openlines/"
			),
			"menu_item_id" => "menu_openlines",
			"top_menu_id" => "top_menu_id_openlines"
		),
		""
	);
}*/

if (CModule::IncludeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::isMainMenuEnabled())
{
	$arMenu[] = array(
		GetMessage("MENU_TELEPHONY_SECTION"),
		"/telephony/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_telephony",
				"/telephony/"
			),
			"class" => "menu-telephony",
			"menu_item_id" => "menu_telephony",
			"top_menu_id" => "top_menu_id_telephony"
		),
		""
	);
}

if (CModule::IncludeModule('im'))
{
	$arMenu[] = [
		GetMessage("MENU_CONFERENCE_SECTION"),
		"/conference/",
		[],
		[
			"class" => "menu-conference",
			"menu_item_id" => "menu_conference",
			"top_menu_id" => "top_menu_id_conference",
			"is_beta" => true
		],
		""
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

$arMenu[] = array(
	GetMessage("MENU_CONTACT_CENTER"),
	"/contact_center/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_contact_center",
			"/contact_center/"
		),
		"menu_item_id"=>"menu_contact_center",
		"top_menu_id" => "top_menu_id_contact_center"
	),
	""
);




$aMenuLinks = $arMenu;
