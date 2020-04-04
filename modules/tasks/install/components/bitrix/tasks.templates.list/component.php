<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$cUserId = \Bitrix\Tasks\Util\User::getId();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SOCNET_MODULE_NOT_INSTALLED"));
	return;
}
if (!\Bitrix\Tasks\Util\User::isAuthorized())
{
	$APPLICATION->AuthForm("");
	return;
}

$loggedInUserId = (int) $cUserId;

// actions, actions..
if (
	($_SERVER['REQUEST_METHOD'] === 'POST')
	&& check_bitrix_sessid() 
	&& isset($_POST, $_POST['module'], $_POST['action'], $_POST['controller_id']) 
	&& ($_POST['module'] === 'tasks')
	&& ($_POST['controller_id'] === 'tasks.templates.list') // to aviod conflict of several components placed at the same page
)
{
	CUtil::JSPostUnescape();

	switch ($_POST['action'])
	{
		case 'group_action':
			if ( ! isset($_POST['subaction'], $_POST['elements_ids'], $_POST['value']) )
			{
				CTaskAssert::logError('[0x12e5e15a] ');
				break;
			}

			if ($_POST['elements_ids'] === 'all')
			{
				/*
				if ( ! isset($_POST['arFilter']))
				{
					CTaskAssert::logError('[0x46ef37f8] ');
					break;
				}

				$arFilter = json_decode($_POST['arFilter'], true);

				if ( ! is_array($arFilter) )
				{
					CTaskAssert::logError('[0x19aa7a1d] ');
					break;
				}

				if (array_key_exists('CHECK_PERMISSIONS', $arFilter))
					unset($arFilter['CHECK_PERMISSIONS']);

				if (count($arFilter) == 0)
				{
					CTaskAssert::logError('[0xe7b4f47e] ');
					return;
				}

				$arFilter['CHECK_PERMISSIONS'] = 'Y';
				*/

				$arFilter = array('BASE_TEMPLATE_ID' => intval($arParams['BASE_TEMPLATE_ID']) ? intval($arParams['BASE_TEMPLATE_ID']) : false);
			}
			else
			{
				$unfilteredTaskIds = array_filter(
					array_map(
						'intval', explode(',', $_POST['elements_ids'])
					)
				);
				if (count($unfilteredTaskIds) == 0)
				{
					CTaskAssert::logError('[0x5f5f7fc7] no items given');
					break;
				}

				$arFilter = array('ID' => $unfilteredTaskIds);
			}

			// Select templates choosen
			$templateIds = array();
			$res = CTaskTemplates::GetList(array(), $arFilter, array('ID'), array(
				'USER_ID' => $loggedInUserId,		// check permissions for current user
				'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
			));
			while ($template = $res->fetch())
				$templateIds[] = (int) $template['ID'];

			//$value = null;
			$processedItems = $notProcessedItems = 0;
			switch($_POST['subaction'])
			{
				case 'remove':
					foreach ($templateIds as $templateId)
					{
						// ka-boom!
						$templateInstance = new \Bitrix\Tasks\Item\Task\Template($templateId);
						$deleteResult = $templateInstance->delete();
						if($deleteResult->isSuccess())
						{
							++$processedItems;
						}
						else
						{
							++$notProcessedItems;
						}
					}
				break;

				default:
					CTaskAssert::logError('[0x8a1747a5] unknown subaction: ' . $_POST['subaction']);
				break;
			}
		break;

		default:
			CTaskAssert::logError('[0x8b300a99] unknown action: ' . $_POST['action']);
		break;
	}

	LocalRedirect($APPLICATION->GetCurPageParam("", array("sessid")));
}

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = trim($arParams["GROUP_VAR"]);
if (strlen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams["TASK_ID"] = intval($arParams["TASK_ID"]);

$arResult["ACTION"] = ($arParams["TASK_ID"] > 0 ? "edit" : "create");

$arParams["USER_ID"] = intval($arParams["USER_ID"]) > 0 ? intval($arParams["USER_ID"]) : $cUserId;

$arParams["GROUP_ID"] = intval($arParams["GROUP_ID"]);

//user paths
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"]);
if (strlen($arParams["PATH_TO_USER_TASKS"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_REPORT"] = trim($arParams["PATH_TO_USER_TASKS_REPORT"]);
if (strlen($arParams["PATH_TO_USER_TASKS_REPORT"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_REPORT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_report&".$arParams["USER_VAR"]."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TEMPLATES"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_templates&".$arParams["USER_VAR"]."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = trim($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
if (strlen($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]) <= 0)
{
	$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_templates_template&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TEMPLATE_VAR"]."=#template_id#&".$arParams["ACTION_VAR"]."=#action#");
}
$arParams["PATH_TO_USER_PROFILE"] = trim($arParams["PATH_TO_USER_PROFILE"]);

$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
$arParams["PATH_TO_REPORTS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_REPORT"]);
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);

$rsUser = CUser::GetByID($arParams["USER_ID"]);
if ($user = $rsUser->Fetch())
{
	$arResult["USER"] = $user;
}
else
{
	return;
}

$arParams["PATH_TO_TEMPLATES"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

// order
if (isset($_GET["SORTF"]) && in_array($_GET["SORTF"], array("TITLE", "DEADLINE", "CREATED_BY", "RESPONSIBLE_LAST_NAME")) && isset($_GET["SORTD"]) && in_array($_GET["SORTD"], array("ASC", "DESC")))
{
	$arResult["ORDER"] = $arOrder = array($_GET["SORTF"] => $_GET["SORTD"]);
}
elseif (isset($arParams["ORDER"]))
{
	$arOrder = $arParams["ORDER"];
}
else
{
	$arOrder = array("TITLE" => "ASC");
}

$arResult["ORDER"] = $arOrder;

if(!is_array($arResult["FILTER"]))
	$arResult["FILTER"] = array();

if(is_array($arParams['FILTER']))
	$arResult["FILTER"] = $arParams['FILTER'];
else
{
	$arResult["FILTER"] = array(
		'BASE_TEMPLATE_ID' => intval($arParams['BASE_TEMPLATE_ID']) ? intval($arParams['BASE_TEMPLATE_ID']) : 0
	);
}

// filtering by user is always performed
//$arResult["FILTER"]["CREATED_BY"] = (int) $arParams['USER_ID'];

$rsTemplates = CTaskTemplates::GetList(
	$arOrder,
	$arResult["FILTER"],
	array(
		'NAV_PARAMS' => array(
			'nPageSize' => intval($arParams["ITEMS_COUNT"]) > 0 ? $arParams["ITEMS_COUNT"] : 10,
			'bDescPageNumbering' => false
		)
	),
	array(
		'USER_ID' => $cUserId,		// check permissions for current user
		'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
	),
	array('*', 'BASE_TEMPLATE_ID')
);

//'TEMPLATE_CHILDREN_COUNT'

$arResult["NAV_STRING"] = $rsTemplates->GetPageNavString(GetMessage("TASKS_TITLE_TEMPLATES"), "arrows");
$arResult["NAV_PARAMS"] = $rsTemplates->GetNavParams();

$arResult["TEMPLATES"] = array();
$ids = array();
while($template = $rsTemplates->GetNext())
{
	unset($template['ALLOW_TIME_TRACKING']);
	$template['TEMPLATE_CHILDREN_COUNT'] = 0;

	$arResult["TEMPLATES"][$template['ID']] = $template;
	$ids[] = $template['ID'];
}

// need to count REACHABLE sub-templates...
$childCounts = \Bitrix\Tasks\Internals\Helper\Task\Template\Dependence::getDirectChildCount($ids, array(
	'USER_ID' => $cUserId
));
foreach($childCounts as $parentId => $cCount)
{
	if(array_key_exists($parentId, $arResult["TEMPLATES"]))
	{
		$arResult["TEMPLATES"][$parentId]['TEMPLATE_CHILDREN_COUNT'] = $cCount;
	}
}

// need to calculate available operations
$ops = \Bitrix\Tasks\Util\User::getAccessOperationsForEntity('task_template');
$allowed = \Bitrix\Tasks\Internals\Helper\Task\Template\Access::getAvailableOperations($ids, array(
	'USER_ID' => $cUserId
));
foreach($allowed as $itemId => $itemOps)
{
	if(array_key_exists($itemId, $arResult["TEMPLATES"]))
	{
		$flipped = array();
		foreach($itemOps as $opId)
		{
			$flipped[ToUpper($ops[$opId]['NAME'])] = true;
		}
		$arResult["TEMPLATES"][$itemId]['ALLOWED_ACTIONS'] = $flipped;
	}
}

##########################

if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("TASKS_TITLE_MY_TEMPLATES"));
}

if ($arParams["SET_NAVCHAIN"] != "N")
{
	$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
	$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE_TEMPLATES"));
}

$this->IncludeComponentTemplate();