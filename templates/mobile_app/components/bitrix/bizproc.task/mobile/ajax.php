<?
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arJsonData = array();

if (!CModule::IncludeModule("bizproc"))
	$arJsonData["error"] = "Module is not installed";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["action"] == "doTask" && check_bitrix_sessid())
{
	if ($_POST["TASK_ID"] > 0)
	{
		$dbTask = CBPTaskService::GetList(
			array(),
			array("ID" => $_POST["TASK_ID"], "USER_ID" => $GLOBALS["USER"]->GetID()),
			false,
			false,
			array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS", 'USER_STATUS')
		);
		$curTask = $dbTask->GetNext();

		if ($curTask)
		{
			if (isset($curTask['USER_STATUS']) && class_exists('CBPTaskUserStatus') && $curTask['USER_STATUS'] > CBPTaskUserStatus::Waiting)
			{
				$arJsonData["success"] = "Y";
			}
			else
			{
				$arErrorsTmp = array();
				if (CBPDocument::PostTaskForm($curTask, $GLOBALS["USER"]->GetID(), $_REQUEST + $_FILES, $arErrorsTmp, $GLOBALS["USER"]->GetFormattedName(false)))
				{
					$arJsonData["success"] = "Y";
				}
				else
				{
					$arError = array();
					foreach ($arErrorsTmp as $e)
						$arError[] = $e["message"];

					$arJsonData["error"] = implode("", $arError);
				}
			}
		}
		else
		{
			$arJsonData["error"] = "Task not found.";
		}
	}
	else
	{
		$arJsonData["error"] = "TASK_ID is empty.";
	}
}

global $APPLICATION;
$APPLICATION->RestartBuffer();
echo \Bitrix\Main\Web\Json::encode($arJsonData);
CMain::FinalActions();
die;