<?

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

$SITE_ID = '';
if (isset($_GET["SITE_ID"]) && is_string($_GET['SITE_ID']))
	$SITE_ID = substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["SITE_ID"]), 0, 2);

if ($SITE_ID <> '')
	define("SITE_ID", $SITE_ID);

if (isset($_GET["GROUP_SITE_ID"]) && is_string($_GET["GROUP_SITE_ID"]))
	$GLOBALS["GROUP_SITE_ID"] = substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["GROUP_SITE_ID"]), 0, 2);
elseif($SITE_ID <> '')
	$GLOBALS["GROUP_SITE_ID"] = $SITE_ID;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("functions.php");

if (!$USER->IsAuthorized())
	die();

CModule::IncludeModule('intranet');
CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);

$arSubDeps = CTasks::GetSubordinateDeps();
$dbRes = CUser::GetList($by='ID', $order='ASC', array('ID' => $USER->GetID()), array('SELECT' => array('UF_DEPARTMENT')));
$arManagers = array();
if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && count($arRes['UF_DEPARTMENT']) > 0)
{
	$arManagers = array_keys(CTasks::GetDepartmentManagers($arRes['UF_DEPARTMENT'], $USER->GetID()));
}

$bSubordinateOnly = isset($_GET["S_ONLY"]) && $_GET["S_ONLY"] == "Y";

if (isset($_REQUEST["nt"]))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_REQUEST["nt"]), $matches);
	$nameTemplate = implode("", $matches[0]);
}
else
{
	$nameTemplate = CSite::GetNameFormat(false);
}

if ($_REQUEST['MODE'] == 'EMPLOYEES' && (!CModule::IncludeModule('extranet') || CExtranet::IsIntranetUser() || $SECTION_ID == 'extranet'))
{
	if ($SECTION_ID != 'extranet')
		$SECTION_ID = intval($_REQUEST['SECTION_ID']);

	$arUsers = TasksGetDepartmentUsers($SECTION_ID, $SITE_ID, $arSubDeps, $arManagers, $_REQUEST['SHOW_INACTIVE_USERS'], $nameTemplate);
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arUsers);

	CMain::FinalActions(); // to make events work on bitrix24
	die();
}
elseif ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();
	$search = $_REQUEST['SEARCH_STRING'];
	$arUsers = array();

	$arFilter = array(
		"ACTIVE" => ((isset($_REQUEST["SHOW_INACTIVE_USERS"]) && ($_REQUEST["SHOW_INACTIVE_USERS"] === "Y")) ? "" : "Y"),
		"NAME_SEARCH" => $search
	);

	if (
		!isset($_REQUEST["SHOW_INACTIVE_USERS"])
		|| ($_REQUEST["SHOW_INACTIVE_USERS"] !== "Y")
	)
	{
		$arFilter["CONFIRM_CODE"] = false;
	}

	if (
		IsModuleInstalled("bitrix24")
		&& !IsModuleInstalled("extranet")
	)
		$arFilter["!UF_DEPARTMENT"] = false;

	if ($bSubordinateOnly)
		$arFilter["UF_DEPARTMENT"] = $arSubDeps;

	// Prevent using users, that doesn't activate it's account
	// http://jabber.bx/view.php?id=29118
	if (IsModuleInstalled('bitrix24'))
		$arFilter['!LAST_LOGIN'] = false;

	$arSelectFields = array(
		'PERSONAL_PHOTO', 
		'PERSONAL_GENDER',
		'ID',
		'NAME',
		'LAST_NAME',
		'SECOND_NAME',
		'LOGIN',
		'EMAIL',
		'WORK_POSITION',
		'PERSONAL_PROFESSION',
	);

	$limitUsersCount = 10;
	$dbRes = CUser::GetList(
		$by = 'last_name', 
		$order = 'asc',
		$arFilter,
		array(
			'SELECT'     => array('UF_DEPARTMENT'),
			'FIELDS'     => $arSelectFields,
			'NAV_PARAMS' => array('nTopCount' => $limitUsersCount)	// selects only 10 users
			)
		);

	while ($arRes = $dbRes->NavNext(false))
	{
		$arPhoto = array('IMG' => '');

		if (!$arRes['PERSONAL_PHOTO'])
		{
			switch ($arRes['PERSONAL_GENDER'])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
			}
			$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $SITE_ID);
		}

		if ($arRes['PERSONAL_PHOTO'] > 0)
			$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);

		$arUsers[] = array(
			'ID' => $arRes['ID'],
			'NAME' => CUser::FormatName($nameTemplate, $arRes, true, false),
			'LOGIN' => $arRes['LOGIN'],
			'EMAIL' => $arRes['EMAIL'],
			'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
			'PHOTO' => $arPhoto['CACHE']['src'],
			'HEAD' => false,
			'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
			'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
			'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
		);
	}
	$arUsers = array_values(array_filter($arUsers, "FilterViewableUsers"));
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arUsers);

	CMain::FinalActions(); // to make events work on bitrix24
	die();
}
?>