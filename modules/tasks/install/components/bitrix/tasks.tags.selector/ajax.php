<?php
define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->RestartBuffer();

CModule::IncludeModule('tasks');

$arAffectedTasks = array();

if (check_bitrix_sessid())
{
	if (sizeof($_POST["oldNames"]))
	{
		$rsRenamedTags = CTaskTags::GetList(array(), array("USER_ID" => $USER->GetID(), "NAME" => $_POST["oldNames"]));
		while($arTag = $rsRenamedTags->Fetch())
		{
			if (!in_array($arTag["TASK_ID"], $arAffectedTasks))
			{
				$arAffectedTasks[] = $arTag["TASK_ID"];
			}
		}
		foreach($_POST["oldNames"] as $key=>$value)
		{
			if ($_POST["oldNames"][$key] != $_POST["newNames"][$key])
			{
				CTaskTags::Rename($_POST["oldNames"][$key], $_POST["newNames"][$key], $USER->GetID());
			}
		}
	}

	if (sizeof($_POST["deleted"]))
	{
		$rsDeletedTags = CTaskTags::GetList(array(), array("USER_ID" => $USER->GetID(), "NAME" => $_POST["deleted"]));
		while($arTag = $rsDeletedTags->Fetch())
		{
			if (!in_array($arTag["TASK_ID"], $arAffectedTasks))
			{
				$arAffectedTasks[] = $arTag["TASK_ID"];
			}
		}
		CTaskTags::Delete(array("USER_ID" => $USER->GetID(), "NAME" => $_POST["deleted"]));
	}

	if (CModule::IncludeModule('search') && sizeof($arAffectedTasks))
	{
		$rsTasks = CTasks::GetList(array(), array("ID" => $arAffectedTasks));
		while($arTask = $rsTasks->Fetch())
		{
			$rsTaskTags = CTaskTags::GetList(array(), array("TASK_ID" => $arTask["ID"]));
			$arTags = array();
			while($tag = $rsTaskTags->Fetch())
			{
				$arTags[] = $tag["NAME"];
			}

			$task = \CTaskItem::getInstance($arTask['ID'], $USER->GetID());
			try
			{
				$taskData = $task->getData(false);
				CTasks::Index($taskData, $arTags);
			}
			catch (TasksException $e)
			{

			}
		}
	}

	CMain::FinalActions(); // to make events work on bitrix24
}