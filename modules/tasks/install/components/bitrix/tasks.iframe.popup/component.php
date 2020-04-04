<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

// initialize path to task
$arParams["PATH_TO_USER_TASKS_TASK"] = isset($arParams["PATH_TO_USER_TASKS_TASK"]) ? trim($arParams["PATH_TO_USER_TASKS_TASK"]) : "";
if (strlen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}

$arParams["USER_ID"] = \Bitrix\Tasks\Util\User::getId();
$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);

if($this->__templateName != 'legacy')
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest();
	$arResult['IFRAME'] = $request['IFRAME'] == 'Y';

	$iFrameType = '';
	if($request['IFRAME_TYPE'])
	{
		$iFrameType = $request['IFRAME_TYPE'];
	}

	// only the following iframe types allowed
	if(!in_array($iFrameType, array('SIDE_SLIDER', '')))
	{
		$iFrameType = '';
	}

	$arResult['IFRAME_TYPE'] = $iFrameType;

	$initialized = CTasksPerHitOption::get('tasks', 'componentTaskIframePopupAlreadyRunned');
	if(!$initialized)
	{
		CTasksPerHitOption::set('tasks', 'componentTaskIframePopupAlreadyRunned', true);
	}

	$arResult['INITIALIZED'] = $initialized;
}
else // left for backward compatiblity, unncescessary data for old templates
{
	// Mark that we are called not first at this hit. Template will skip some work in this case.
	$arResult['FIRST_RUN_AT_HIT'] = true;
	$arParams['ALLOW_NOT_FIRST_RUN_OPTIMIZATION'] = isset($arParams['ALLOW_NOT_FIRST_RUN_OPTIMIZATION']) ? $arParams['ALLOW_NOT_FIRST_RUN_OPTIMIZATION'] : 'Y';

	$bAlreadyRun = CTasksPerHitOption::get('tasks', 'componentTaskIframePopupAlreadyRunned');

	if ($bAlreadyRun)
		$arResult['FIRST_RUN_AT_HIT'] = false;
	else
		CTasksPerHitOption::set('tasks', 'componentTaskIframePopupAlreadyRunned', true);

	$arResult['OPTIMIZE_REPEATED_RUN'] = false;
	if ($arParams['ALLOW_NOT_FIRST_RUN_OPTIMIZATION'] === 'Y')
	{
		// If it isn't first run => optimize
		if ($arResult['FIRST_RUN_AT_HIT'] === false)
			$arResult['OPTIMIZE_REPEATED_RUN'] = true;
	}

	$arResult['COMPANY_WORKTIME'] = array(
		'START' => array('H' => 9, 'M' => 0, 'S' => 0),
		'END' => array('H' => 19, 'M' => 0, 'S' => 0),
	);
	if(CModule::IncludeModule('calendar'))
	{
		$calendarSettings = CCalendar::GetSettings(array('getDefaultForEmpty' => false));

		$time = explode('.', (string) $calendarSettings['work_time_start']);
		if(intval($time[0]))
			$arResult['COMPANY_WORKTIME']['START']['H'] = intval($time[0]);
		if(intval($time[1]))
			$arResult['COMPANY_WORKTIME']['START']['M'] = intval($time[1]);

		$time = explode('.', (string) $calendarSettings['work_time_end']);
		if(intval($time[0]))
			$arResult['COMPANY_WORKTIME']['END']['H'] = intval($time[0]);
		if(intval($time[1]))
			$arResult['COMPANY_WORKTIME']['END']['M'] = intval($time[1]);
	}
}

$this->IncludeComponentTemplate();
