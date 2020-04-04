<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Type;

CModule::IncludeModule('intranet');

$nowDate = new Type\DateTime();
$toDate = new Type\DateTime();

// parse period
switch ($arParams['PERIOD'])
{
	case 'year':
		$fromDate = $last12months = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n')-12, 1));
		$interval = 'month';
		$dateFormat = 'Y-m';
		$axisDateFormat = "M\nY";
		$axisCursorDateFormat = "f\nY";
		break;

	case 'month':
		$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n'), date('j')-30));
		$interval = 'day';
		$dateFormat = 'Y-m-d';
		$axisDateFormat = "D\nd.m";
		$axisCursorDateFormat = "l\nd M Y";
		break;

	case 'week':
		$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n'), date('j')-7));
		$interval = 'day';
		$dateFormat = 'Y-m-d';
		$axisDateFormat = "D\nd.m";
		$axisCursorDateFormat = "l\nd M Y";
		break;

	case 'custom_day':
		// for today, from 00:00 till 23:59
		$arParams['CUSTOM_DAY'] = isset($arParams['CUSTOM_DAY']) ? $arParams['CUSTOM_DAY'] : $_REQUEST['CUSTOM_DAY'];

		$fromDate = new Type\DateTime($arParams['CUSTOM_DAY'].' 00:00:00', 'Y-m-d H:i:s');
		$fromDate->add('-1 hour');

		$toDate = clone $fromDate;
		$toDate->add('+25 hour');

		$interval = 'hour';
		$dateFormat = 'Y-m-d H:00:00';
		$axisDateFormat = "H";
		$axisCursorDateFormat = "d M Y\n".(IsAmPmMode() ? "g:i a" : "H:i");
		break;

	default:
		// for today, from 00:00 till 23:59
		$fromDate = Type\DateTime::createFromTimestamp(mktime(-1, 0, 0));
		$toDate = Type\DateTime::createFromTimestamp(mktime(24, 0, 0));
		$interval = 'hour';
		$dateFormat = 'Y-m-d H:00:00';
		$axisDateFormat = "H";
		$axisCursorDateFormat = array(
			"today" => "today, ".(IsAmPmMode() ? "g:i a" : "H:i")
		);
}

$fromDateServer = clone $fromDate;
$toDateServer = clone $toDate;

if ($interval == 'hour')
{
	// with timezones
	$userTimeOffset = CTimeZone::GetOffset();
	$fromUserTimeInterval = -$userTimeOffset.' seconds';

	$fromDateServer->add($fromUserTimeInterval);
	$toDateServer->add($fromUserTimeInterval);
}

if (empty($arParams['USER_ID']))
{
	$arParams['USER_ID'] = $USER->getId();
}

$sumActivity = 0;
$sumAvgCompanyActivity = 0;
$sumAvgDepartmentActivity = 0;

$sectionData = array();

$sectionField = empty($arParams['SECTION']) ? 'TOTAL' : $arParams['SECTION'];

// get sections info
$sectionData = array();

$sectionList = array(
	'Bitrix\Intranet\UStat\SocnetEventHandler',
	'Bitrix\Intranet\UStat\LikesEventHandler',
	'Bitrix\Intranet\UStat\TasksEventHandler',
	'Bitrix\Intranet\UStat\ImEventHandler',
	'Bitrix\Intranet\UStat\DiskEventHandler',
	'Bitrix\Intranet\UStat\MobileEventHandler',
	'Bitrix\Intranet\UStat\CrmEventHandler'
);

foreach ($sectionList as $k => $sectionClass)
{
	$sectionData[$sectionClass::SECTION] = array(
		'class' => $sectionClass,
		'title' => $sectionClass::getTitle(),
		'activity' => 0,
		'lead_activity' => 0,
		'lead_activity_prc' => 0
	);
}

// call API
$rawData = \Bitrix\Intranet\UStat\UStat::getUsersGraphData(
	$arParams['USER_ID'], $fromDate, $toDate, $interval, $sectionField
);


// fill data with empty dates
$tmpDate = clone $fromDateServer;

while ($tmpDate->getTimestamp() < $toDateServer->getTimestamp())
{
	$key = $tmpDate->format($dateFormat);

	$tmpTZTimestamp = $tmpDate->getTimestamp();

	if ($interval == 'hour')
	{
		// first, add hour to axis title
		$tmpDate->add('+ 1 '.$interval);

		// adopt to user time
		$tmpTZTimestamp = $tmpDate->getTimestamp() + CTimeZone::GetOffset();
	}

	$data[$key] = array(
		'date' => FormatDate($axisDateFormat, $tmpTZTimestamp),
		//'activity' => 0,
		//'company_activity' => 0,
		//'department_activity' => 0,
		//'self_activity' => 0,
		'cursor_date' => nl2br(FormatDate($axisCursorDateFormat, $tmpTZTimestamp))
	);

	if ($interval == 'hour')
	{
		// remove axis fake hour
		$tmpDate->add('- 1 '.$interval);
	}

	if ($interval == 'day')
	{
		// add customday property
		$data[$key]['custom_day'] = $tmpDate->format($dateFormat);
	}

	if ($tmpDate->getTimestamp() < $nowDate->getTimestamp())
	{
		$data[$key]['activity'] = 0;
		$data[$key]['company_activity'] = 0;
		$data[$key]['department_activity'] = 0;
		$data[$key]['self_activity'] = 0;
	}

	$tmpDate->add('+ 1 '.$interval);
}

if ($interval == 'hour')
{
	// replace last 00:00 to 24:00
	$data[$key]['date'] = '24';
	$data[$key]['cursor_date'] = str_replace('00:00', '24:00', $data[$key]['cursor_date']);
}

// fill data
foreach ($rawData['data'] as $k => $v)
{
	$data[$k]['activity'] = $v[$sectionField];

	$sumActivity += $v[$sectionField];

	// fill section data
	foreach ($sectionData as $_section => &$_sectionData)
	{
		$_sectionData['activity'] += $v[$_section];
	}
}

// company average
$companyAverageData = \Bitrix\Intranet\UStat\UStat::getDepartmentAverageGraphData(
	0, $fromDate, $toDate, $interval, $sectionField
);

foreach ($companyAverageData as $k => $companyData)
{
	$data[$k]['company_activity'] = $companyData['AVG_ACTIVITY'];
	$sumAvgCompanyActivity += $companyData['AVG_ACTIVITY'];
}

// department average
$usersDepartments = \Bitrix\Intranet\UStat\UStat::getUsersDepartments();
$userDepartments = $usersDepartments[$arParams['USER_ID']];
$userDepartmentId = current($userDepartments);

$departmentAverageData = \Bitrix\Intranet\UStat\UStat::getDepartmentAverageGraphData(
	$userDepartmentId, $fromDate, $toDate, $interval, $sectionField
);

foreach ($departmentAverageData as $k => $departmentData)
{
	$data[$k]['department_activity'] = $departmentData['AVG_ACTIVITY'];
	$sumAvgDepartmentActivity += $departmentData['AVG_ACTIVITY'];
}

// get dept title
$companyStructure = \CIntranetUtils::getStructure();
$departmentTitle = $companyStructure['DATA'][$userDepartmentId]['NAME'];

// max data for sections
$maxUsersActivity = \Bitrix\Intranet\UStat\UStat::getMaxUserActivity($fromDate, $toDate, $interval);

if (!empty($maxUsersActivity))
{
	foreach ($maxUsersActivity as $section => $maxValue)
	{
		if ($section == 'TOTAL')
		{
			continue;
		}

		$sectionData[$section]['lead_activity'] = $maxValue;

		if ($maxValue > 0)
		{
			// if user has at least 90% of max, he is good enough for being absolutely green
			$sectionData[$section]['lead_activity_prc'] = min((
				round($sectionData[$section]['activity'] / ($sectionData[$section]['lead_activity']*0.9) * 100)
			), 100);
		}
	}
}

// user info
$result = CUser::GetList(
	($dummy=''), ($dummy=''), array("ID" => join('|', array($arParams['USER_ID'], $USER->getId()))),
	array("FIELDS" => array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "PERSONAL_GENDER"))
);

$usersInfo = array();

while ($row = $result->fetch())
{
	if(!empty($row["PERSONAL_PHOTO"]))
	{
		$row["PERSONAL_PHOTO_file"] = CFile::GetFileArray($row["PERSONAL_PHOTO"]);

		$row["PERSONAL_PHOTO_resized"] = CFile::ResizeImageGet(
			$row["PERSONAL_PHOTO_file"],
			array("width" => 30, "height" => 30	),
			BX_RESIZE_IMAGE_EXACT,
			false
		);

		if ($row["PERSONAL_PHOTO_resized"] !== false)
		{
			$row['AVATAR_SRC'] = $row["PERSONAL_PHOTO_resized"]["src"];
			$row["PERSONAL_PHOTO_img"] = CFile::ShowImage($row["PERSONAL_PHOTO_resized"]["src"], 42, 42, "border=0 align='right'");
		}
	}

	// full name
	$row['FULL_NAME'] = $row['NAME']."\n".$row['LAST_NAME'];

	if (!strlen(trim($row['FULL_NAME'])))
	{
		$row['FULL_NAME'] = $row['LOGIN'];
	}

	$usersInfo[$row['ID']] = $row;
}

$compareWithMyself = false;

// myself if BY_ID is not me. let's compare
if ($arParams['USER_ID'] != $USER->getId())
{
	$compareWithMyself = true;

	$selfData = \Bitrix\Intranet\UStat\UStat::getUsersGraphData(
		$USER->getId(), $fromDate, $toDate, $interval, $sectionField
	);

	foreach ($selfData['data'] as $k => $_selfData)
	{
		$data[$k]['self_activity'] = $_selfData[$sectionField];
	}
}

// if user has a right to tell about servies
$allowTellAbout = false;
if ($USER->IsAdmin()
	|| CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID())
	|| in_array((int)$USER->getId(), \Bitrix\Intranet\UStat\UStat::getHeadsOfDepartments(), true)
)
{
	$allowTellAbout = true;
}

// done!

$arResult = array(
	'SECTION' => $sectionField,
	'INTERVAL' => $interval,
	'SUM_ACTIVITY' => $sumActivity,
	'SUM_AVG_COMPANY_ACTIVITY' => $sumAvgCompanyActivity,
	'SUM_AVG_DEPARTMENT_ACTIVITY' => $sumAvgDepartmentActivity,
	'TOP_POSITION' => $rawData['rating']['position'],
	'USERS_INFO' => $usersInfo,
	'DEPARTMENT_TITLE' => $departmentTitle,
	'ALLOW_TELL_ABOUT' => $allowTellAbout,
	'SECTION_DATA' => $sectionData,
	'DATA' => array_values($data),
	'COMPARE_WITH_MYSELF' => $compareWithMyself
);

//var_dump($arResult, $rawData, $arParams);

$this->IncludeComponentTemplate();
