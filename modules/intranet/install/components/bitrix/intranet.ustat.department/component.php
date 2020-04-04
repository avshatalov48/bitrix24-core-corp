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
		// first, define what is `today` for user
		$userNowTime = time() + CTimeZone::GetOffset();
		$userNowDay = date('j', $userNowTime);
		$userNowMonth = date('n', $userNowTime);
		$userNowYear = date('Y', $userNowTime);

		// then generate full dates from 00:00 till 23:59
		$fromDate = Type\DateTime::createFromTimestamp(mktime(-1, 0, 0, $userNowMonth, $userNowDay, $userNowYear));
		$toDate = Type\DateTime::createFromTimestamp(mktime(24, 0, 0, $userNowMonth, $userNowDay, $userNowYear));
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

// call API
$rawData = \Bitrix\Intranet\UStat\UStat::getDepartmentGraphData(
	$arParams['DEPARTMENT_ID'], $fromDate, $toDate, $interval
);


// format raw data
$data = array();
$maxActivity = 1;
$sumActivity = 0;

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
		'involvement' => 0,
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
$sectionField = empty($arParams['SECTION']) ? 'TOTAL' : $arParams['SECTION'];

// get section involvement
if ($sectionField != 'TOTAL')
{
	$sectionInvolvement = \Bitrix\Intranet\UStat\UStat::getSectionInvolvement(0, $sectionField, $fromDate, $toDate, $interval);
}

foreach ($rawData as $k => $v)
{
	if (!isset($data[$k]))
	{
		// sometimes it happens on custom day. better to recheck and fix initial problem
		continue;
	}

	/** @var Type\DateTime[] $v */
	if ($sectionField == 'TOTAL')
	{
		$involvement = $v['INVOLVEMENT'];
	}
	elseif (isset($sectionInvolvement[$k]) && $sectionInvolvement[$k]['TOTAL_USERS'] > 0)
	{

		$involvement = round(
			$sectionInvolvement[$k][$sectionField.'_USAGE'] / $sectionInvolvement[$k]['TOTAL_USERS'] * 100
		);
	}
	else
	{
		$involvement = 0;
	}

	$data[$k]['activity'] = $v[$sectionField];
	$data[$k]['involvement'] = $involvement;

	$maxActivity = max($maxActivity, $v[$sectionField]);
	$sumActivity += $v[$sectionField];
}

if ($interval == 'hour' && $arParams['PERIOD'] !== 'custom_day')
{
	// it is today
	// make current hour as a projection and rewrite activity
	$tmpDate = clone $nowDate;
	$nowKey = $tmpDate->format($dateFormat);
	$currentActivity = \Bitrix\Intranet\UStat\UStat::getCurrentActivity(0, $sectionField);
	$data[$nowKey]['activity'] = $currentActivity;

	$maxActivity = max($maxActivity, $currentActivity);

	$tmpDate->add('- 1 hour');
	$previousKey = $tmpDate->format($dateFormat);
	$data[$previousKey]['dash_length_line'] = 3;
}

// get summary involvement for company
if ($sectionField == 'TOTAL')
{
	$sumInvolvement = \Bitrix\Intranet\UStat\UStat::getDepartmentSummaryInvolvement(0, $fromDate, $toDate, $interval);
}

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
		'involvement' => 0
	);
}



// set section activity
foreach ($rawData as $_data)
{
	foreach ($sectionData as $_section => &$_sectionData)
	{
		$_sectionData['activity'] += $_data[$_section];
	}
}
unset($_sectionData);

// set section involvement
$sectionInvolvement = \Bitrix\Intranet\UStat\UStat::getSectionsSummaryInvolvement($fromDate, $toDate, $interval);

foreach ($sectionData as $_section => &$_sectionData)
{
	$_sectionData['involvement'] = round(
		$sectionInvolvement[$_section.'_USAGE'] / $sectionInvolvement['USERS_COUNT'] * 100
	);
}

// get summary involvement for current section
if ($sectionField != 'TOTAL')
{
	$sumInvolvement = $sectionData[$sectionField]['involvement'];
}

// get users rating
$usersData = \Bitrix\Intranet\UStat\UStat::getUsersGraphData($USER->getId(), $fromDate, $toDate, $interval, $sectionField);
$usersRating = $usersData['rating'];

// 5 in a row 3rd pos
//$usersRating = array(
//	'top' => array(
//		1 => array('USER_ID' => '2', 'ACTIVITY' => '112',),
//		2 => array('USER_ID' => '3', 'ACTIVITY' => '10',),
//		3 => array('USER_ID' => '1', 'ACTIVITY' => '8',),
//		4 => array('USER_ID' => '7', 'ACTIVITY' => '0',),
//		5 => array('USER_ID' => '23', 'ACTIVITY' => '0',),),
//	'position' => 3,
//);

// 3+1  27th pos
//$usersRating = array(
//	'top' => array(
//		1 => array('USER_ID' => '2', 'ACTIVITY' => '112',),
//		2 => array('USER_ID' => '3', 'ACTIVITY' => '10',),
//		3 => array('USER_ID' => '4', 'ACTIVITY' => '8',),
//		4 => array('USER_ID' => '7', 'ACTIVITY' => '0',),
//		5 => array('USER_ID' => '23', 'ACTIVITY' => '0',),
//		27 => array('USER_ID' => '1', 'ACTIVITY' => '0',),
//	),
//	'position' => 27,
//);

$topUserIds = array($USER->getId());

foreach ($usersRating['top'] as $userInfo)
{
	$topUserIds[] = $userInfo['USER_ID'];
}

$result = CUser::GetList(
	($dummy=''), ($dummy=''), array("ID" => join('|', $topUserIds)),
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
			array("width" => 42, "height" => 42),
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
	$row['FULL_NAME'] = $row['NAME'].' '.$row['LAST_NAME'];

	if (!strlen(trim($row['FULL_NAME'])))
	{
		$row['FULL_NAME'] = $row['LOGIN'];
	}

	$usersInfo[$row['ID']] = $row;
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

// test
if (array_key_exists('utest', $_REQUEST))
{
	foreach ($data as &$sub)
	{
		$sub['involvement'] = rand(0,90);
	}
}


// build result
$arResult = array(
	'SECTION' => $sectionField,
	'INTERVAL' => $interval,
	'SUM_INVOLVEMENT' => $sumInvolvement,
	'SUM_ACTIVITY' => $sumActivity,
	'MAX_ACTIVITY' => $maxActivity,
	'ALLOW_TELL_ABOUT' => $allowTellAbout,
	'USERS_RATING' => $usersRating,
	'USERS_INFO' => $usersInfo,
	'SECTION_DATA' => $sectionData,
	'DATA' => array_values($data)
);

//var_dump($arResult, $rawData, $arParams);

$this->IncludeComponentTemplate();
