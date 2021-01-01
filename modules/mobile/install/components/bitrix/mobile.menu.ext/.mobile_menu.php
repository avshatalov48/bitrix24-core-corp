<?php
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $USER;

/**
 * @var CAllUser $USER
 */

$allowedFeatures = array();
if (CModule::IncludeModule("socialnetwork"))
{
	$arUserActiveFeatures = CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $USER->getId());
	$arSocNetFeaturesSettings = CSocNetAllowed::getAllowedFeatures();

	$allowedFeatures = array();
	foreach (array("tasks", "files", "calendar") as $feature)
	{
		if (in_array($feature, array('calendar')))
		{
			$allowedFeatures[$feature] =
				array_key_exists($feature, $arSocNetFeaturesSettings) &&
				array_key_exists("allowed", $arSocNetFeaturesSettings[$feature]) &&
				(
					(
						in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings[$feature]["allowed"]) &&
						is_array($arUserActiveFeatures) &&
						in_array($feature, $arUserActiveFeatures)
					)
					|| in_array(SONET_ENTITY_GROUP, $arSocNetFeaturesSettings[$feature]["allowed"])
				)
			;
		}
		else
		{
			$allowedFeatures[$feature] =
				array_key_exists($feature, $arSocNetFeaturesSettings) &&
				array_key_exists("allowed", $arSocNetFeaturesSettings[$feature]) &&
				in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings[$feature]["allowed"]) &&
				is_array($arUserActiveFeatures) &&
				in_array($feature, $arUserActiveFeatures)
			;
		}
	}
}

$isExtranetUser = (\CModule::includeModule("extranet") && !\CExtranet::isIntranetUser());
$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk');

$menuStructure = array(
	array(
		"name"=>Loc::getMessage("MB_SEC_FAVORITE"),
		"hidden"=>false,
		"sort"=>100,
		"items"=> array(
			array(
				"name"=>Loc::getMessage("MB_LIVE_FEED"),
				"attrs"=>array(
					"data-url"=> SITE_DIR."mobile/",
					"data-modern-style"=>"Y",
					"data-name"=> Loc::getMessage("MB_LIVE_FEED"),
					"data-page-id"=>"main_feed",
					"id"=>"main_feed",
				),
				"counter"=>array(
					"id"=>"menu-counter-live-feed",
				),
				"hidden"=>false,
				"css_class"=> "menu-icon-lenta",
			),
			array(
				"name"=>Loc::getMessage("MB_CHAT_AND_CALLS"),
				"hidden"=>false,
				"attrs"=>array(
					"onclick"=> "BXIM.openRecentList();",
				),
				"counter"=>array(
					"id"=>"menu-counter-im-message",
				),
				"css_class"=> "menu-icon-msg",
			),
			array(
				"name"=>Loc::getMessage("MB_TASKS_MAIN_MENU_ITEM"),
				"attrs"=>array(
					"data-url"=> SITE_DIR."mobile/tasks/snmrouter/?routePage=roles",
					"data-name"=> Loc::getMessage("MB_TASKS_MAIN_MENU_ITEM"),
					"data-modern-style"=>"Y",
					"data-page-id"=>"tasks_list",
					"id"=>"tasks_list",
				),
				"counter"=>array(
					"id"=>"menu-counter-tasks_total",
				),
				"hidden"=>!(\Bitrix\Main\ModuleManager::isModuleInstalled('tasks') && $allowedFeatures["tasks"]),
				"css_class"=> "menu-icon-tasks",
			),
			array(
				"name"=>Loc::getMessage("MB_BP_MAIN_MENU_ITEM"),
				"attrs"=>array(
					"data-url"=> SITE_DIR."mobile/bp/?USER_STATUS=0",
					"data-name"=> Loc::getMessage("MB_BP_MAIN_MENU_ITEM"),
					"data-modern-style"=>"Y",
					"data-page-id"=>"bp_list",
					"id"=>"bp_list",
				),
				"counter"=>array(
					"id"=>"menu-counter-bp_tasks",
				),

				"hidden"=> ($isExtranetUser || !\Bitrix\Main\ModuleManager::isModuleInstalled("bizproc")),
				"css_class"=> "menu-icon-bizproc",
			),
			array(
				"name"=>Loc::getMessage("MB_CALENDAR_LIST"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.calendarList(". $USER->GetID().");",
					"id"=>"calendar_list",
				),
				"css_class"=> "menu-icon-calendar",
				"hidden"=>!(\Bitrix\Main\ModuleManager::isModuleInstalled('calendar') && $allowedFeatures["calendar"]),
			),

			array(
				"name"=>Loc::getMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.diskList({type: 'user', entityId:".$USER->GetID()."}, '/');",
					"id"=>"doc_user",
				),
				"hidden"=> !$diskEnabled || !$allowedFeatures["files"],
				"id"=>"doc_user",
				"css_class"=> "menu-icon-disk",
			),
			array(
				"name"=>Loc::getMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.webdavList('user/".$USER->GetID()."/');",
					"id"=>"doc_user",
				),
				"hidden"=> $diskEnabled || !$allowedFeatures["files"],
				"css_class"=> "menu-icon-disk",
			),
			array(
				"name"=>Loc::getMessage($isExtranetUser ? "MB_CONTACTS" : "MB_COMPANY"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.userList(".($isExtranetUser?"true":"false").");",
				),
				"css_class"=> "menu-icon-employees",
			),
			array(
				"name"=>Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.diskList({type: 'common', entityId:'shared_files_s1'}, '/');",
					"id"=>"doc_shared",
				),
				"hidden"=> !$diskEnabled || $isExtranetUser || !$allowedFeatures["files"],

				"css_class"=> "menu-icon-files",
			),
			array(
				"name"=>Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
				"attrs"=>array(
					"onclick"=>"MobileMenu.webdavList('shared/');",
					"id"=>"doc_shared",
				),
				"hidden"=> $diskEnabled || $isExtranetUser || !$allowedFeatures["files"],

				"css_class"=> "menu-icon-files",
			),
		)
	)
);

/**
 * Marketplace apps
 */

if (CModule::IncludeModule("rest"))
{
	$arMenuApps = array();
	$arUserGroupCode = $USER->GetAccessCodes();
	$numLocalApps = 0;

	$dbApps = \Bitrix\Rest\AppTable::getList(array(
		'order' => array("ID" => "ASC"),
		'filter' => array(
			"=ACTIVE" => \Bitrix\Rest\AppTable::ACTIVE,
			"=MOBILE" => \Bitrix\Rest\AppTable::ACTIVE
		),
		'select' => array(
			'ID', 'STATUS', 'ACCESS', 'MENU_NAME' => 'LANG.MENU_NAME', 'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME', 'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME'
		)
	));

	while ($arApp = $dbApps->fetch())
	{
		if ($arApp["STATUS"] == \Bitrix\Rest\AppTable::STATUS_LOCAL)
		{
			$numLocalApps++;
		}

		$lang = in_array(LANGUAGE_ID, array("ru", "en", "de")) ? LANGUAGE_ID : LangSubst(LANGUAGE_ID);
		if ($arApp["MENU_NAME"] <> '' || $arApp['MENU_NAME_DEFAULT'] <> '' || $arApp['MENU_NAME_LICENSE'] <> '')
		{
			$appRightAvailable = false;
			if (\CRestUtil::isAdmin())
			{
				$appRightAvailable = true;
			}
			elseif (!empty($arApp["ACCESS"]))
			{
				$rights = explode(",", $arApp["ACCESS"]);
				foreach ($rights as $rightID)
				{
					if (in_array($rightID, $arUserGroupCode))
					{
						$appRightAvailable = true;
						break;
					}
				}
			}
			else
			{
				$appRightAvailable = true;
			}

			if ($appRightAvailable)
			{
				$appName = $arApp["MENU_NAME"];

				if ($appName == '')
				{
					$appName = $arApp['MENU_NAME_DEFAULT'];
				}
				if ($appName == '')
				{
					$appName = $arApp['MENU_NAME_LICENSE'];
				}

				$arMenuApps[] = Array(
					"name" => htmlspecialcharsbx($appName),
					"attrs"=>array(
						"id" => $arApp["ID"],
						"data-mp-app-id"=>$arApp["ID"],
						"data-mp-app"=>"Y",
						"data-mp-app-name"=>htmlspecialcharsbx($appName),
						"data-url" => "/mobile/marketplace/?id=" . $arApp["ID"],
					)
				);
			}
		}
	}

	if(count($arMenuApps) > 0)
	{
		$menuStructure[] = array(
			"name"=>Loc::getMessage("MB_MARKETPLACE_GROUP_TITLE_2"),
			"sort"=>110,
			"hidden"=>CMobile::getInstance()->getApiVersion()<=15,
			"items"=> $arMenuApps
		);
	}
}

/**
 * CRM menu
 */

if (
	!$bExtranet
	&& IsModuleInstalled('crm')
	&& CModule::IncludeModule('crm')
	&& CCrmPerms::IsAccessEnabled()
)
{
	$userPerms = CCrmPerms::GetCurrentUserPermissions();
	$menuStructure[] = array(
		"name"=>"CRM",
		"sort"=>120,
		"hidden"=>false,
		"items"=>array(
			array(
				"name"=>Loc::getMessage("MB_CRM_ACTIVITY"),
				"hidden"=>false,
				"attrs"=>array(
					"data-url"=> "/mobile/crm/activity/list.php",
					"data-modern-style"=>"N",
					"data-name"=>Loc::getMessage("MB_CRM_ACTIVITY"),
					"data-page-id"=>"crm_activity_list",
					"id"=>"crm_activity_list",
				),
				"css_class"=> "menu-icon-mybusiness",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_CONTACT"),
				"hidden"=>$userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/contact/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_CONTACT"),
					"data-page-id"=>"crm_contact_list",
					"id"=>"crm_contact_list",
				),
				"css_class"=> "menu-icon-contacts",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_COMPANY"),
				"hidden"=>$userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/company/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_COMPANY"),
					"data-page-id"=>"crm_company_list",
					"id"=>"crm_company_list",
				),
				"css_class"=> "menu-icon-company",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_DEAL"),
				"hidden"=>$userPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/deal/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_DEAL"),
					"data-page-id"=>"crm_deal_list",
					"id"=>"crm_deal_list",
				),
				"css_class"=> "menu-icon-deals",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_INVOICE"),
				"hidden"=>$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/invoice/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_INVOICE"),
					"data-page-id"=>"crm_invoice_list",
					"id"=>"crm_invoice_list",
				),
				"css_class"=> "menu-icon-invoice",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_QUOTE"),
				"hidden"=>$userPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/quote/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_QUOTE"),
					"data-page-id"=>"crm_quote_list",
					"id"=>"crm_quote_list",
				),
				"css_class"=> "menu-icon-quote",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_LEAD"),
				"hidden"=>$userPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/lead/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_LEAD"),
					"data-page-id"=>"crm_lead_list",
					"id"=>"crm_lead_list",
				),
				"css_class"=> "menu-icon-leads",
			),
			array(
				"name"=>Loc::getMessage("MB_CRM_PRODUCT"),
				"hidden"=> !$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'),
				"attrs"=>array(
					"data-url"=> "/mobile/crm/product/",
					"data-modern-style"=>"Y",
					"data-name"=>Loc::getMessage("MB_CRM_PRODUCT"),
					"data-page-id"=>"crm_product_list",
					"id"=>"crm_product_list",
				),
				"css_class"=> "menu-icon-products",
			),
		)
	);
}


/**
 * Groups
 */

$groups = array();
$extranetGroups = array();


if (CModule::IncludeModule("socialnetwork"))
{
	$strGroupSubjectLinkTemplate = SITE_DIR . "mobile/log/?group_id=#group_id#";
	$extGroupID = array();
	$arGroupFilterMy = array(
		"USER_ID" => $USER->GetID(),
		"<=ROLE" => SONET_ROLES_USER,
		"GROUP_ACTIVE" => "Y",
		"!GROUP_CLOSED" => "Y",
	);

	// Extranet group
	if (CModule::IncludeModule("extranet") && !CExtranet::IsExtranetSite())
	{
		$arGroupFilterMy["GROUP_SITE_ID"] = CExtranet::GetExtranetSiteID();
		$dbGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arGroupFilterMy,
			false,
			false,
			array('ID', 'GROUP_ID', 'GROUP_NAME', 'GROUP_SITE_ID')
		);
		$arExtSGGroupTmp = array();
		while ($arGroups = $dbGroups->GetNext())
		{
			$arExtSGGroupTmp[$arGroups["GROUP_ID"]] = array(
				"name"=>$arGroups["GROUP_NAME"],
				"attrs"=>array(
					"data-name"=>$arGroups["GROUP_NAME"],
					"data-url"=>str_replace("#group_id#", $arGroups["GROUP_ID"], $strGroupSubjectLinkTemplate),
					"data-modern-style"=>"Y"
				),
				"counter"=>array(
					"id" => "SG" . $arGroups["GROUP_ID"]
				)
			);

			$extGroupID[] = $arGroups["GROUP_ID"];
		}
	}

	$arGroupIDCurrentSite = array();

	// Socialnetwork
	$arGroupFilterMy["GROUP_SITE_ID"] = SITE_ID;
	$dbGroups = CSocNetUserToGroup::GetList(
		array("GROUP_NAME" => "ASC"),
		$arGroupFilterMy,
		false,
		false,
		array('ID', 'GROUP_ID', 'GROUP_NAME', 'GROUP_SITE_ID')
	);

	while ($arGroups = $dbGroups->GetNext())
	{
		$arGroupIDCurrentSite[] = $arGroups['GROUP_ID'];

		if (in_array($arGroups['GROUP_ID'], $extGroupID))
		{
			continue;
		}

		$groups[] = array(
			"name"=>$arGroups["GROUP_NAME"],
			"attrs"=>array(
				"data-name"=>$arGroups["GROUP_NAME"],
				"data-url"=>str_replace("#group_id#", $arGroups["GROUP_ID"], $strGroupSubjectLinkTemplate),
				"data-modern-style"=>"Y"
			),
			"counter"=>array(
				"id" => "SG" . $arGroups["GROUP_ID"]
			)
		);
	}

	foreach ($arExtSGGroupTmp as $groupID => $arGroupItem)
	{
		if (in_array($groupID, $arGroupIDCurrentSite))
		{
			$extranetGroups[] = $arGroupItem;
		}
	}
}


if(!empty($groups))
{
	$menuStructure[] = array(
		"name"=> Loc::getMessage("MB_SEC_GROUPS"),
		"sort"=>130,
		"hidden"=>false,
		"css_style"=>"menu-section-groups",
		"items"=>$groups
	);
}

if(!empty($extranetGroups))
{
	$menuStructure[] = array(
		"name"=> Loc::getMessage("MB_SEC_EXTRANET"),
		"sort"=>140,
		"hidden"=>false,
		"css_style"=>"menu-section-groups",
		"items"=>$extranetGroups
	);
}



return $menuStructure;