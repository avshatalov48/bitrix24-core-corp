<?php
/** @var array $arParams */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Extranet\User;
use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ScrumLimit;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\KpiLimit;
$isMenu = isset($arParams['MENU_MODE']) && $arParams['MENU_MODE'] === true;
$arResult['BX24_RU_ZONE'] = ModuleManager::isModuleInstalled('bitrix24')
	&& preg_match("/^(ru)_/", COption::GetOptionString("main", "~controller_group_name", ""))
;

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');
//$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$strIframe = '';
$strIframe2 = '';
if(isset($_REQUEST['IFRAME']) && !$arParams['MENU_MODE'])
{
	$strIframe = '?IFRAME='.($_REQUEST['IFRAME'] === 'Y' ? 'Y' : 'N');
	$strIframe2 = '&IFRAME='.($_REQUEST['IFRAME'] === 'Y' ? 'Y' : 'N');
}

if ($helper->checkHasFatals())
{
	return;
}

$arResult['TEMPLATE_DATA'] = array(// contains data generated in result_modifier.php
);
$arResult['JS_DATA'] = array(
	'userId' => $arResult['USER_ID'] ?? null,
	'ownerId' => $arResult['OWNER_ID'] ?? null,
	'groupId' => $arParams['GROUP_ID'] ?? null,
	'filterId' => $arParams['FILTER_ID'] ?? null,
	'use_ajax_filter' => isset($arParams['USE_AJAX_ROLE_FILTER']) && $arParams['USE_AJAX_ROLE_FILTER'] === 'Y',
	'text_sl_effective'=>GetMessage('TASKS_PANEL_TEXT_EFFECTIVE'),
	'show_sl_effective'=> !CUserOptions::GetOption('spotlight', 'view_date_tasks_sl_effective')
);

$arResult['ITEMS'] = array();

$urlRoleTemplate = (
	$arParams['GROUP_ID'] <= 0 || $arParams['PROJECT_VIEW'] === 'Y'
		? RouteDictionary::PATH_TO_USER_TASKS_LIST
		: RouteDictionary::PATH_TO_GROUP_TASKS_LIST
);
$tasksLink = CComponentEngine::makePathFromTemplate(
	$urlRoleTemplate,
	[
		'group_id' => $arParams['GROUP_ID'],
		'user_id' => $arParams['USER_ID']
	]
);

$taskEditLink = \Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
	'action' => 'edit',
	'task_id' => 0,
	'user_id' => $arParams['USER_ID'],
	'group_id' => $arParams['GROUP_ID']
]);
$taskEditLink = new Uri($taskEditLink);
$taskEditLink->addParams([
	'ta_sec' => \Bitrix\Tasks\Helper\Analytics::SECTION['tasks'],
	'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['horizontal_menu'],
]);

$arResult['ITEMS'][] = array(
	"TEXT" => GetMessage("TASKS_PANEL_TAB_TASKS"),
	"URL" => $tasksLink . '?F_CANCEL=Y&F_SECTION=ADVANCED&F_STATE=sR' . $strIframe2,
	"ID" => "view_all",
	'CLASS' => $arParams['PROJECT_VIEW'] === 'Y' ? '' : 'tasks_role_link',
	'SUB_LINK' => [
		'CLASS' => '',
		'URL' => $taskEditLink->getUri(),
	],
	"IS_ACTIVE" => (isset($arParams["MARK_TEMPLATES"]) && $arParams["MARK_TEMPLATES"] !== "Y" &&
					isset($arParams["MARK_SECTION_PROJECTS"]) && $arParams["MARK_SECTION_PROJECTS"] !== "Y" &&
					isset($arParams["MARK_SECTION_PROJECTS_LIST"]) && $arParams["MARK_SECTION_PROJECTS_LIST"] !== "Y" &&
					isset($arParams["MARK_SECTION_SCRUM_LIST"]) && $arParams["MARK_SECTION_SCRUM_LIST"] !== "Y" &&
					isset($arParams["MARK_SECTION_MANAGE"]) && $arParams["MARK_SECTION_MANAGE"] !== "Y" &&
					isset($arParams["MARK_SECTION_EMPLOYEE_PLAN"]) && $arParams["MARK_SECTION_EMPLOYEE_PLAN"] !== "Y" &&
					isset($arParams["MARK_SECTION_REPORTS"]) && $arParams["MARK_SECTION_REPORTS"] !== "Y") &&
					isset($arParams['DEFAULT_ROLEID']) &&
					($arParams["MARK_RECYCLEBIN"] ?? 'N') !== "Y" &&
					($arParams["MARK_SECTION_EFFECTIVE"] ?? 'N') !== "Y" &&
					($arParams['DEFAULT_ROLEID'] === 'view_all' || $arParams['DEFAULT_ROLEID'] === ''), // need refactoring,
	'COUNTER' => $arResult['TOTAL'],
	'COUNTER_ID' => Counter\CounterDictionary::COUNTER_MEMBER_TOTAL,
	'COUNTER_ACTIVE' => 'Y'
);

// base items
foreach ($arResult['ROLES'] as $roleId => $role)
{
	$arResult['ITEMS'][] = array(
		'TEXT' => $role['TEXT'],
		'URL' => $tasksLink.'?'.$role['HREF'].'&clear_filter=Y'.$strIframe2,
		'ON_CLICK' => '',
		//		'ON_CLICK'=>$arParams['USE_AJAX_ROLE_FILTER'] == 'N'
		//			? null
		//			: 'BX.Tasks.Component.TopMenu.getInstance("topmenu").filter("'.strtolower($roleId).'")',
		'ID' => mb_strtolower($roleId),
		'CLASS' => $arParams['PROJECT_VIEW'] === 'Y' ? '' : 'tasks_role_link',
		'IS_ACTIVE' =>
			(
				isset($arParams['MARK_ACTIVE_ROLE'])
				&& $arParams['MARK_ACTIVE_ROLE'] === 'Y'
				&& array_key_exists('IS_ACTIVE', $role)
				&& $role['IS_ACTIVE']
			)
			||
			(
				isset($arParams['DEFAULT_ROLEID'])
				&& $arParams['DEFAULT_ROLEID'] === mb_strtolower($roleId)
			),
		'COUNTER' => $role['COUNTER'],
		'COUNTER_ID' => $role['COUNTER_ID'],
		'PARENT_ITEM_ID' => 'view_all',
	);
}

$createGroupLink = CComponentEngine::makePathFromTemplate(
	$arParams['TASKS_GROUP_CREATE_URL_TEMPLATE'],
	['user_id' => $arParams['LOGGED_USER_ID']]
);

$isSpacesAvailable = SpaceService::isAvailable(true);

$projectsUrl = $isSpacesAvailable ? '/spaces/' : $tasksLink.'projects/'.$strIframe;

$arResult['ITEMS'][] = [
	"TEXT" => $isSpacesAvailable ? GetMessage("TASKS_PANEL_TAB_SPACE") : GetMessage("TASKS_PANEL_TAB_PROJECTS"),
	"URL" => $projectsUrl,
	"ID" => "view_projects",
	"IS_ACTIVE" => ($arParams["MARK_SECTION_PROJECTS_LIST"] === "Y"),
	'SUB_LINK' => ['CLASS' => '', 'URL' => $createGroupLink],
	'COUNTER' => $arResult['PROJECTS_COUNTER'],
	'COUNTER_ID' => 'tasks_projects_counter',
];

$createScrumLink = CComponentEngine::makePathFromTemplate(
	$arParams['TASKS_SCRUM_CREATE_URL_TEMPLATE'],
	['user_id' => $arParams['LOGGED_USER_ID']]
);

$scrumUri = new Uri($createScrumLink);
$scrumUri->addParams([
	'PROJECT_OPTIONS' => [
		'scrum' => true,
	]
]);

$arResult['ITEMS'][] = [
	"TEXT" => GetMessage("TASKS_PANEL_TAB_SCRUM"),
	"URL" => $tasksLink.'scrum/'.$strIframe,
	"ID" => "view_scrum",
	"IS_ACTIVE" => ($arParams["MARK_SECTION_SCRUM_LIST"] === "Y"),
	'SUB_LINK' => [
		'CLASS' => '',
		'ON_CLICK' => 'BX.Tasks.Component.TopMenu.getInstance("topmenu").createScrum("'
			. $scrumUri->getUri() . '", "'.ScrumLimit::getSidePanelId().'");'
		,
	],
	'COUNTER' => $arResult['SCRUM_COUNTER'],
	'COUNTER_ID' => 'tasks_scrum_counter',
	'IS_NEW' => true,
];

if ($arParams["SHOW_SECTION_MANAGE"] !== "N")
{
	$counter = intval($arResult["SECTION_MANAGE_COUNTER"]);
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_MANAGE"),
		"URL" => $tasksLink.'departments/'.$strIframe,
		"ID" => "view_departments",
		'COUNTER' => $counter,
		'COUNTER_ID' => 'departments_counter',
		"IS_ACTIVE" => $arParams["MARK_SECTION_MANAGE"] === "Y",
	);
}

$efficiencyItem = [
	"TEXT" => GetMessage("TASKS_PANEL_TAB_EFFECTIVE"),
	"URL" => $tasksLink."effective/".$strIframe,
	"ID" => "view_effective",
	"MAX_COUNTER_SIZE" => 100,
	"IS_ACTIVE" => (isset($arParams["MARK_SECTION_EFFECTIVE"]) && $arParams["MARK_SECTION_EFFECTIVE"] === "Y"),
	"COUNTER" => (int)$arResult['EFFECTIVE_COUNTER'],
];
if (
	TaskLimit::isLimitExceeded()
	|| KpiLimit::isLimitExceeded()
)
{
	unset($efficiencyItem['COUNTER']);
}
$arResult['ITEMS'][] = $efficiencyItem;

if (!$arParams['GROUP_ID'] && !User::isExtranet())
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_EMPLOYEE_PLAN"),
		"URL" => $tasksLink.'employee/plan/'.$strIframe,
		"ID" => "view_employee_plan",
		"IS_ACTIVE" => $arParams["MARK_SECTION_EMPLOYEE_PLAN"] === "Y",
		'IS_DISABLED' => true
	);
}

if (
	isset($arParams["SHOW_SECTION_REPORTS"])
	&& $arParams["SHOW_SECTION_REPORTS"] === "Y"
	&& (
		!isset($_REQUEST['IFRAME'])
		|| !$_REQUEST['IFRAME']
		|| $_REQUEST['IFRAME'] !== 'Y'
	)
)
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_REPORTS"),
		"URL" => $tasksLink.'report/'.$strIframe,
		"ID" => "view_reports",
		"IS_ACTIVE" => $arParams["MARK_SECTION_REPORTS"] === "Y",
		'IS_DISABLED' => true
	);
}

if ($arResult["BX24_RU_ZONE"])
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_APPLICATIONS_2"),
		"URL" => \Bitrix\Tasks\Integration\Market\Router::getCategoryPath('tasks'),
		"ID" => "view_apps",
		'IS_DISABLED' => true,
	);
}

if ($arParams["SHOW_SECTION_TEMPLATES"] === "Y")
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_TEMPLATES"),
		"URL" => $tasksLink.'templates/'.$strIframe,
		"ID" => "view_templates",
		"IS_ACTIVE" => $arParams["MARK_TEMPLATES"] === "Y",
		'IS_DISABLED' => true
	);
}
$hideRecycleBin = $arParams['SHOW_SECTION_RECYCLEBIN'] ?? 'Y';
if ($hideRecycleBin !== 'N' && Loader::includeModule('recyclebin'))
{
	$arResult['ITEMS'][] = [
		"TEXT" => Loc::getMessage('TASKS_PANEL_TAB_RECYCLEBIN'),
		"URL" => $tasksLink . RouteDictionary::RECYCLEBIN_SUFFIX . $strIframe,
		"ID" => 'view_recyclebin',
		"IS_ACTIVE" => isset($arParams['MARK_RECYCLEBIN']) && $arParams['MARK_RECYCLEBIN'] === 'Y',
		'IS_DISABLED' => false,
	];
}

if (TaskAccessController::can($arParams['LOGGED_USER_ID'], ActionDictionary::ACTION_TASK_ADMIN))
{
	$rightsButton = [
		'TEXT' => GetMessage('TASKS_PANEL_TAB_CONFIG_PERMISSIONS'),
		'ID' => 'config_permissions',
		'IS_ACTIVE' => false,
		'IS_DISABLED' => true,
	];
	if ($isMenu)
	{
		$rightsButton['URL'] = RouteDictionary::PATH_TO_PERMISSIONS;
	}
	else
	{
		$rightsButton['ON_CLICK'] = 'BX.Tasks.Component.TopMenu.getInstance("topmenu").showConfigPermissions();';
	}
	if (!Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASKS_PERMISSIONS))
	{
		$rightsButton['IS_LOCKED'] = true;
	}

	$arResult['ITEMS'][] = $rightsButton;
}

if (class_exists('\Bitrix\Intranet\Settings\Tools\ToolsManager'))
{
	foreach ($arResult['ITEMS'] as $key => $item)
	{
		if (!\Bitrix\Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByMenuId($item['ID']))
		{
			unset($arResult['ITEMS'][$key]);
		}
	}
}