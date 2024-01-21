<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule("security"))
{
	return;
}

$bitrix24Options = [
	"redirect_sid" => Bitrix\Main\Security\Random::getString(32),
	"security_event_db_active" => "N",
	"security_event_format" => "#AUDIT_TYPE# | #BX24_HOST_NAME# | #URL# | #VARIABLE_NAME# | #VARIABLE_VALUE_BASE64#",
	"security_event_syslog_active" => "Y",
	"security_event_syslog_facility" => LOG_SYSLOG,
	"security_event_syslog_priority" => LOG_ALERT,
	'otp_default_algo' => 'totp',
	'otp_enabled' => 'Y',
	'otp_allow_remember' => 'Y',
	"otp_allow_recovery_codes" => "Y",
];

foreach ($bitrix24Options as $key => $value)
{
	COption::SetOptionString("security", $key, $value);
}

RegisterModuleDependences("main", "OnBeforeLocalRedirect", "security", "CSecurityRedirect", "BeforeLocalRedirect", "1");

//for otp
RegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin", "100");
RegisterModuleDependences("main", "OnAfterUserLogout", "security", "CSecurityUser", "OnAfterUserLogout", "100");

$otpRecheckAgent = 'Bitrix\Security\Mfa\OtpEvents::onRecheckDeactivate();';
CAgent::RemoveAgent($otpRecheckAgent, "security");
CAgent::Add([
	"NAME" => $otpRecheckAgent,
	"MODULE_ID" => "security",
	"ACTIVE" => "Y",
	"AGENT_INTERVAL" => 3600,
	"IS_PERIOD" => "N",
]);

// Add & attach new task for edit OTP in public.
$tasks = [
	'security_otp_public' => [
		"LETTER" => "E",
		"BINDING" => "module",
		"OPERATIONS" => [
			'security_edit_user_otp',
			'security_otp_settings_read',
			'security_otp_settings_write',
		],
	],
];
CTask::AddFromArray('security', $tasks);

$rsDB = \Bitrix\Main\TaskTable::getList([
	'select' => ['ID'],
	'filter' => ['=MODULE_ID' => 'security', '=NAME' => 'security_otp_public'],
]);
if ($arTask = $rsDB->fetch())
{
	$groupTask = \Bitrix\Main\GroupTaskTable::getList([
		'select' => ['GROUP_ID'],
		'filter' => ['=GROUP_ID' => 1, '=TASK_ID' => $arTask['ID']],
	]);
	if (!$groupTask->fetch())
	{
		\Bitrix\Main\GroupTaskTable::add([
			'GROUP_ID' => 1,
			'TASK_ID' => $arTask['ID'],
		]);
	}
}
