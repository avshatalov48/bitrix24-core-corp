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
		$arParams['CUSTOM_DAY'] = !empty($arParams['CUSTOM_DAY']) ? $arParams['CUSTOM_DAY'] : $_REQUEST['CUSTOM_DAY'];

		try
		{
			$fromDate = new Type\DateTime($arParams['CUSTOM_DAY'].' 00:00:00', 'Y-m-d H:i:s');
		}
		catch (\Bitrix\Main\ObjectException $e)
		{
			$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0));
		}

		$toDate = clone $fromDate;
		$toDate->add('+23 hour 59 minute');

		// day, because we need empty records for all users
		$interval = 'day';
		$dateFormat = 'Y-m-d H:00:00';
		$axisDateFormat = "H";
		$axisCursorDateFormat = "d M Y\n".(IsAmPmMode() ? "g:i a" : "H:i");
		break;

	default:
		// for today, from 00:00 till 23:59
		$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0));
		$toDate = Type\DateTime::createFromTimestamp(mktime(23, 59, 59));

		// day, because we need empty records for all users
		$interval = 'day';
		$dateFormat = 'Y-m-d H:00:00';
		$axisDateFormat = "H";
		$axisCursorDateFormat = array(
			"today" => "today, ".(IsAmPmMode() ? "g:i a" : "H:i")
		);
}

$sectionField = empty($arParams['SECTION']) ? 'TOTAL' : $arParams['SECTION'];

$arParams['NON_INVOLVED'] = (isset($arParams['NON_INVOLVED']) && $arParams['NON_INVOLVED']) || (isset($_REQUEST['LIST']) && $_REQUEST['LIST'] === 'involve');

if (!isset($arParams['OFFSET']))
{
	$arParams['OFFSET'] = isset($_REQUEST['OFFSET']) ? (int) $_REQUEST['OFFSET'] : 0;
}

if (!isset($arParams['TOP_ACTIVITY']) && isset($_REQUEST['TOP_ACTIVITY']))
{
	$arParams['TOP_ACTIVITY'] = $_REQUEST['TOP_ACTIVITY'];
}

$data = \Bitrix\Intranet\UStat\UStat::getUsersTop(1, 0, $fromDate, $toDate, $interval, $sectionField, $arParams['NON_INVOLVED'], $arParams['OFFSET'], 20);

$topUserIds = array();

foreach ($data as $_data)
{
	$topUserIds[] = $_data['USER_ID'];
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

$activeList = array();
$inactiveList = array();

foreach ($data as $_data)
{
	if ($_data['ACTIVITY'] > 0)
	{
		$activeList[] = $_data;
	}

	if (!$_data['IS_INVOLVED'])
	{
		$inactiveList[] = $_data;
	}
}


// build result
$arResult = array(
	'SECTION' => $sectionField,
	'INTERVAL' => $interval,
	'USERS_INFO' => $usersInfo,
	'DATA' => array_values($data)
);

$this->IncludeComponentTemplate();

function getNumberEnding($number, $titles)
{
	$cases = array (2, 0, 1, 1, 1, 2);
	return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
}
