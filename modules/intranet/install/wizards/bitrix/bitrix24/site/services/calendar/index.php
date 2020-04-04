<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("intranet")||!CModule::IncludeModule("calendar"))
	return;

// User's calendar type
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'user',
		'NAME' => GetMessage('CAL_TYPE_USER_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

// Group's calendar type
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'group',
		'NAME' => GetMessage('CAL_TYPE_GROUP_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

// Company calendar
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'group',
		'NAME' => GetMessage('CAL_TYPE_GROUP_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

$organizationType = \Bitrix\Main\Config\Option::get("intranet", "organization_type");
$typeName = GetMessage('EC_COMPANY_CALENDAR_'.strtoupper($organizationType));
if ($typeName == '')
	$typeName = GetMessage('EC_COMPANY_CALENDAR_');

CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'company_calendar',
		'NAME' => $typeName,
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

// Section
$sectionName = $typeName;
$sectId = CCalendar::SaveSection(
	array(
		'arFields' => Array(
			'CAL_TYPE' => 'company_calendar',
			'ID' => 0,
			'NAME' => $sectionName,
			'DESCRIPTION' => '',
			'COLOR' => '#002056',
			'TEXT_COLOR' => '#FFFFFF',
			'OWNER_ID' => '',
			'IS_EXCHANGE' => false
		)
	)
);

if ($sectId)
{
	CCalendarSect::SavePermissions($sectId, array('G2' => CCalendar::GetAccessTasksByName('calendar_section', 'calendar_edit')));
}

// Add new section to superpose
if ($sectId)
{
	CUserOptions::SetOption("calendar", "superpose_displayed_default", $sectId, true);
}

$CUR_SET = CCalendar::GetSettings(array('getDefaultForEmpty' => false));
if (is_array($CUR_SET))
{
	$CUR_SET['path_to_type_company_calendar'] = '/calendar/';
	CCalendar::SetSettings($CUR_SET);
}

COption::SetOptionString("intranet", "calendar_2", "Y");

$dateStart = mktime(12, 0, 0, date("m"), date("d")+1, date("Y"));
$dateStart = GetTime($dateStart , "FULL");
$dateEnd = mktime(14, 0, 0, date("m"), date("d")+1, date("Y"));
$dateEnd = GetTime($dateEnd, "FULL");
/**********
$id = CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'user',
		'OWNER_ID' => 1,
		'NAME' => GetMessage("W_IB_CALENDAR_EMP_ABS"),
		'DT_FROM' => $dateStart,
		'DT_TO' => $dateEnd,
		'DESCRIPTION' => '',
	),
	'userId' => 1,
	'autoDetectSection' => true,
	'autoCreateSection' => true
));
**********/
COption::SetOptionString('calendar', 'pathes_for_sites', false);
COption::SetOptionString("calendar", 'pathes_sites', serialize(array('s1', 'ex')));
COption::SetOptionString("calendar", 'pathes_'.WIZARD_SITE_ID, serialize(array(
	'path_to_user' => '/company/personal/user/#user_id#/',
	'path_to_user_calendar' => '/company/personal/user/#user_id#/calendar/',
	'path_to_group' => '/workgroups/group/#group_id#/',
	'path_to_group_calendar' => '/workgroups/group/#group_id#/calendar/',
	'path_to_vr'=>'',
	'path_to_rm'=>''
)));

COption::SetOptionString("calendar", "year_holidays", GetMessage("CAL_YEAR_HOLIDAYS"));
?>