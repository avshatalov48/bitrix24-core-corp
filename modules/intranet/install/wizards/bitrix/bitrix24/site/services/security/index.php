<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("security"))
	return;

$bitrix24Options = array(
	"redirect_sid" => Bitrix\Main\Security\Random::getString(32),
	"security_event_db_active" => "N",
	"security_event_format" => "#AUDIT_TYPE# | #BX24_HOST_NAME# | #URL# | #VARIABLE_NAME# | #VARIABLE_VALUE_BASE64#",
	"security_event_syslog_active" => "Y",
	"security_event_syslog_facility" => LOG_SYSLOG,
	"security_event_syslog_priority" => LOG_ALERT,
	'otp_default_algo' =>  'totp',
	'otp_enabled' => 'Y',
	'otp_allow_remember' => 'Y',
	"otp_allow_recovery_codes" => "Y"
);

foreach($bitrix24Options as $key => $value)
{
	COption::SetOptionString("security", $key, $value);
}

RegisterModuleDependences("main", "OnBeforeLocalRedirect", "security", "CSecurityRedirect", "BeforeLocalRedirect", "1");

//for otp
RegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin", "100");
RegisterModuleDependences("main", "OnAfterUserLogout", "security", "CSecurityUser", "OnAfterUserLogout", "100");
$otpRecheckAgent = 'Bitrix\Security\Mfa\OtpEvents::onRecheckDeactivate();';
CAgent::RemoveAgent($otpRecheckAgent, "security");
CAgent::Add(array(
	"NAME" => $otpRecheckAgent,
	"MODULE_ID" => "security",
	"ACTIVE" => "Y",
	"AGENT_INTERVAL" => 3600,
	"IS_PERIOD" => "N",
));
/*
if (!\Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") && \Bitrix\Main\Loader::includeModule("security"))
{
	CSecuritySession::activate();
}*/

// Add & attach new task for edit OTP in public.
// Mostly copy&paste from CModule

$sqlMODULE_ID = $DB->ForSQL('security', 50);

$arDBOperations = array();
$rsOperations = $DB->Query("SELECT NAME FROM b_operation WHERE MODULE_ID = '$sqlMODULE_ID'");
while($ar = $rsOperations->Fetch())
	$arDBOperations[$ar["NAME"]] = $ar["NAME"];

$arDBTasks = array();
$rsTasks = $DB->Query("SELECT NAME FROM b_task WHERE MODULE_ID = '$sqlMODULE_ID' AND SYS = 'Y'");
while($ar = $rsTasks->Fetch())
	$arDBTasks[$ar["NAME"]] = $ar["NAME"];

$tasks = array(
	'security_otp_public' => array(
		"LETTER" => "E",
		"BINDING" => "module",
		"OPERATIONS" => array(
			'security_edit_user_otp',
			'security_otp_settings_read',
			'security_otp_settings_write'
		),
	)
);
foreach($tasks as $task_name => $arTask)
{
	$sqlBINDING = isset($arTask["BINDING"]) && $arTask["BINDING"] <> ''? $DB->ForSQL($arTask["BINDING"], 50): 'module';
	$sqlTaskOperations = array();

	if(isset($arTask["OPERATIONS"]) && is_array($arTask["OPERATIONS"]))
	{
		foreach($arTask["OPERATIONS"] as $operation_name)
		{
			$operation_name = mb_substr($operation_name, 0, 50);

			if(!isset($arDBOperations[$operation_name]))
			{
				$DB->Query("
								INSERT INTO b_operation
								(NAME, MODULE_ID, BINDING)
								VALUES
								('".$DB->ForSQL($operation_name)."', '$sqlMODULE_ID', '$sqlBINDING')
							", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$arDBOperations[$operation_name] = $operation_name;
			}

			$sqlTaskOperations[] = $DB->ForSQL($operation_name);
		}
	}

	$task_name = mb_substr($task_name, 0, 100);
	$sqlTaskName = $DB->ForSQL($task_name);

	if(!isset($arDBTasks[$task_name]) && $task_name <> '')
	{
		$DB->Query("
						INSERT INTO b_task
						(NAME, LETTER, MODULE_ID, SYS, BINDING)
						VALUES
						('$sqlTaskName', '".$DB->ForSQL($arTask["LETTER"], 1)."', '$sqlMODULE_ID', 'Y', '$sqlBINDING')
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

	if(!empty($sqlTaskOperations) && $task_name <> '')
	{
		$DB->Query("
						INSERT INTO b_task_operation
						(TASK_ID,OPERATION_ID)
						SELECT T.ID TASK_ID, O.ID OPERATION_ID
						FROM
							b_task T
							,b_operation O
						WHERE
							T.SYS='Y'
							AND T.NAME='$sqlTaskName'
							AND O.NAME in ('".implode("','", $sqlTaskOperations)."')
							AND O.NAME not in (
								SELECT O2.NAME
								FROM
									b_task T2
									inner join b_task_operation TO2 on TO2.TASK_ID = T2.ID
									inner join b_operation O2 on O2.ID = TO2.OPERATION_ID
								WHERE
									T2.SYS='Y'
									AND T2.NAME='$sqlTaskName'
							)
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}

$rsDB = $DB->Query("select ID from b_task where MODULE_ID = 'security' and NAME = 'security_otp_public'");
if ($arTask = $rsDB->Fetch())
{
	$arGroupTask = $DB->Query("SELECT GROUP_ID FROM b_group_task WHERE GROUP_ID = 1 AND TASK_ID = ".$arTask['ID']);
	if (!$arGroupTask->fetch())
	{
		$DB->Query("INSERT INTO b_group_task (GROUP_ID, TASK_ID) VALUES (1, ".$arTask['ID'].")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}