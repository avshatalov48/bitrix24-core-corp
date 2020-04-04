<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!IsModuleInstalled("tasks"))
	return false;

$arGadgetParams["SHOW"] = ($arGadgetParams["SHOW"]?$arGadgetParams["SHOW"]:"Y");

if (strlen(trim($arGadgetParams["TITLE"])) > 0)
	$arGadget["TITLE"] = htmlspecialcharsback($arGadgetParams["TITLE"]);
elseif ($arParams["MODE"] == "SG")
	$arGadget["TITLE"] = GetMessage('GD_TASKS_TITLE_GROUP');
elseif ($arParams["MODE"] == "SU")
	$arGadget["TITLE"] = GetMessage('GD_TASKS_TITLE_USER');

$arGadgetParams["TEMPLATE_NAME"] = ($arGadgetParams["TEMPLATE_NAME"]?$arGadgetParams["TEMPLATE_NAME"]:"part");
$arGadgetParams["SHOW_TITLE"] = ($arGadgetParams["SHOW_TITLE"]?$arGadgetParams["SHOW_TITLE"]:"N");
$arGadgetParams["SHOW_FOOTER"] = ($arGadgetParams["SHOW_FOOTER"]?$arGadgetParams["SHOW_FOOTER"]:"Y");
$arGadgetParams["TITLE"] = ($arGadgetParams["TITLE"]?$arGadgetParams["TITLE"]:"");
$arGadgetParams["OWNER_ID"] = ($arGadgetParams["OWNER_ID"]?$arGadgetParams["OWNER_ID"]:$GLOBALS["USER"]->GetID());
$arGadgetParams["TASK_TYPE"] = ($arGadgetParams["TASK_TYPE"]?$arGadgetParams["TASK_TYPE"]:'user');
$arGadgetParams["FORUM_ID"] = ($arGadgetParams["FORUM_ID"]?$arGadgetParams["FORUM_ID"]:false);

$arGadgetParams["PATH_TO_GROUP_TASKS"] = ($arGadgetParams["PATH_TO_GROUP_TASKS"]?$arGadgetParams["PATH_TO_GROUP_TASKS"]:"/workgroups/group/#group_id#/tasks/");
$arGadgetParams["PATH_TO_GROUP_TASKS_TASK"] = ($arGadgetParams["PATH_TO_GROUP_TASKS_TASK"]?$arGadgetParams["PATH_TO_GROUP_TASKS_TASK"]:"/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/");
$arGadgetParams["PATH_TO_USER_TASKS"] = ($arGadgetParams["PATH_TO_USER_TASKS"]?$arGadgetParams["PATH_TO_USER_TASKS"]:"/company/personal/user/#user_id#/tasks/");
$arGadgetParams["PATH_TO_USER_TASKS_TASK"] = ($arGadgetParams["PATH_TO_USER_TASKS_TASK"]?$arGadgetParams["PATH_TO_USER_TASKS_TASK"]:"/company/personal/user/#user_id#/tasks/task/#action#/#task_id#/");
$arGadgetParams["PATH_TO_TASK"] = (isset($arGadgetParams["PATH_TO_TASK"])?$arGadgetParams["PATH_TO_TASK"]:"/company/personal/user/#user_id#/tasks/");
$arGadgetParams["PATH_TO_TASK_NEW"] = (isset($arGadgetParams["PATH_TO_TASK_NEW"])?$arGadgetParams["PATH_TO_TASK_NEW"]:"/company/personal/user/#user_id#/tasks/task/edit/0/");

$arGadgetParams["PAGE_VAR"] = ($arGadgetParams["PAGE_VAR"]?$arGadgetParams["PAGE_VAR"]:"page");
$arGadgetParams["GROUP_VAR"] = ($arGadgetParams["GROUP_VAR"]?$arGadgetParams["GROUP_VAR"]:"group_id");
$arGadgetParams["VIEW_VAR"] = ($arGadgetParams["VIEW_VAR"]?$arGadgetParams["VIEW_VAR"]:"user_id");
$arGadgetParams["TASK_VAR"] = ($arGadgetParams["TASK_VAR"]?$arGadgetParams["TASK_VAR"]:"task_id");
$arGadgetParams["ACTION_VAR"] = ($arGadgetParams["ACTION_VAR"]?$arGadgetParams["ACTION_VAR"]:"action");

if($arGadgetParams["SHOW"] == "Y"):
	if($arGadgetParams["SHOW_TITLE"] == "Y"):
		?><h4><?= $arGadgetParams["TITLE"] ?></h4><?
	endif;

	$o = $arGadgetParams["ORDER_BY"];
	if (!In_Array($o, array("E", "C", "P")))
		$o = "E";
	$t = $arGadgetParams["TYPE"];
	if (!In_Array($t, array("Z", "U")))
		$t = "Z";

	$arP = Array(
		"ITEMS_COUNT" => $arGadgetParams["ITEMS_COUNT"],
		"PAGE_VAR" => $arGadgetParams["PAGE_VAR"],
		"GROUP_VAR" => $arGadgetParams["GROUP_VAR"],
		"VIEW_VAR" => $arGadgetParams["VIEW_VAR"],
		"TASK_VAR" => $arGadgetParams["TASK_VAR"],
		"ACTION_VAR" => $arGadgetParams["TASK_ACTION_VAR"],
		"PATH_TO_GROUP_TASKS" => $arGadgetParams["PATH_TO_GROUP_TASKS"],
		"PATH_TO_GROUP_TASKS_TASK" => $arGadgetParams["PATH_TO_GROUP_TASKS_TASK"],
		"PATH_TO_GROUP_TASKS_VIEW" => $arGadgetParams["PATH_TO_GROUP_TASKS_VIEW"],
		"PATH_TO_USER_TASKS" => $arGadgetParams["PATH_TO_USER_TASKS"],
		"PATH_TO_USER_TASKS_TASK" => $arGadgetParams["PATH_TO_USER_TASKS_TASK"],
		"PATH_TO_USER_TASKS_VIEW" => $arGadgetParams["PATH_TO_USER_TASKS_VIEW"],
		"FORUM_ID" => $arGadgetParams["FORUM_ID"],
	);

	if ($arGadgetParams["TASK_TYPE"] == "group")
	{
		$arP["FILTER"]["GROUP_ID"] = $arGadgetParams["OWNER_ID"];
		$arP["GROUP_ID"] = $arGadgetParams["OWNER_ID"];
	}
	else
	{
		if ($t == "U")
		{
			$arP["FILTER"]["CREATED_BY"] = $arGadgetParams["OWNER_ID"];
		}
		else
		{
			$arP["FILTER"]["DOER"] = $arGadgetParams["OWNER_ID"];
		}
	}

	$arP["FILTER"]["STATUS"] = array(-2, -1, 1, 2, 3);
	$arP["SET_NAVCHAIN"] = "N";
	$arP["ORDER"] = array((($o == "C") ? "CREATED_DATE" : (($o == "P") ? "PRIORITY" : "DEADLINE")) => (($o == "C") ? "DESC" : (($o == "P") ? "ASC" : "ASC")));

	$APPLICATION->IncludeComponent(
		"bitrix:tasks.list",
		"gadget",
		$arP,
		false,
		array("HIDE_ICONS" => "Y")
	);

	if($arGadgetParams["SHOW_FOOTER"] == "Y"):
		$p = array("user_id"=>$USER->GetID());
		?>
		<br><br>
		<a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK"], $p)?>"><?echo GetMessage("GD_TASKS_LIST")?></a> <a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK"], $p)?>"><img height="7" src="/images/icons/arrows.gif" width="7" border="0" /></a>
		| <a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK_NEW"], $p)?>"><?echo GetMessage("GD_TASK_TASK_NEW")?></a> <a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK_NEW"], $p)?>"><img height="7" src="/images/icons/arrows.gif" width="7" border="0" /></a>
	<?endif;

else:
	echo GetMessage('GD_TASKS_NOT_ALLOWED');
endif;?>