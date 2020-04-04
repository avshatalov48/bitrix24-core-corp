<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("meeting"))
	return ShowError(GetMessage("ML_MODULE_NOT_INSTALLED"));

$arParams['USER_ID'] = $arParams['USER_ID'] ? $arParams['USER_ID'] : $USER->GetID();
$arParams['MEETING_URL'] = $arParams['MEETING_URL'] ? $arParams['MEETING_URL'] : 'meeting.php?MEETING_ID=#MEETING_ID#';
$arParams['MEETING_ADD_URL'] = $arParams['MEETING_ADD_URL'] ? $arParams['MEETING_ADD_URL'] : 'meeting.php';
$arParams['MEETING_EDIT_URL'] = $arParams['MEETING_EDIT_URL'] ? $arParams['MEETING_EDIT_URL'] : 'meeting.php?MEETING_ID=#MEETING_ID#&edit=Y';
$arParams['MEETING_COPY_URL'] = $arParams['MEETING_COPY_URL'] ? $arParams['MEETING_COPY_URL'] : 'meeting.php?MEETING_ID=#MEETING_ID#&COPY=Y';

$arParams['MEETINGS_COUNT'] = intval($arParams['MEETINGS_COUNT']);
if ($arParams['MEETINGS_COUNT'] <= 0)
	$arParams['MEETINGS_COUNT'] = 20;

if (strlen($arParams["NAME_TEMPLATE"]) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arParams['PAGER_TITLE'] = trim($arParams['PAGER_TITLE']);
if (strlen($arParams['PAGER_TITLE']) <= 0)
	$arParams['PAGER_TITLE'] = GetMessage('ML_PAGER_TITLE');

$arResult['MEETINGS'] = array();
$arResult['USERS'] = array();

$arUserIDs = array($arParams['USER_ID']);

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

$arNavParams = array(
	"nPageSize" => $arParams["MEETINGS_COUNT"],
	"bDescPageNumbering" => false,
	"bShowAll" => false
);

$arSubIDs = array($USER->GetID());
$dbUsers = CIntranetUtils::GetSubordinateEmployees($arParams['USER_ID'], true, 'Y', array('ID'));
while ($arUser = $dbUsers->Fetch())
{
	$arSubIDs[] = $arUser['ID'];
}

$arResult['IS_HEAD'] = count($arSubIDs) > 0;

$arFilter = array("USER_ID" => $arParams['USER_ID']);
$arResult['FILTER'] = array('MY' => true);

if ($_REQUEST['FILTER'])
{
	$arFilterValues = $_REQUEST['FILTER'];

	if ($arResult['IS_HEAD'] && !isset($arFilterValues['MY']))
	{
		$arResult['FILTER'] = array(); $arFilter['USER_ID'] = $arSubIDs;
	}

	if (isset($arFilterValues['TITLE']) && strlen(trim($arFilterValues['TITLE'])) > 0)
	{
		$arResult['FILTER']['TITLE'] = $arFilterValues['TITLE'];
		$arFilter['~TITLE'] = '%'.trim($arFilterValues['TITLE']).'%';
	}

	if (isset($arFilterValues['CURRENT_STATE']) && strlen($arFilterValues['CURRENT_STATE'])==1 && in_array($arFilterValues['CURRENT_STATE'], array(CMeeting::STATE_PREPARE, CMeeting::STATE_ACTION, CMeeting::STATE_CLOSED)))
		$arResult['FILTER']['CURRENT_STATE'] = $arFilter['CURRENT_STATE'] = $arFilterValues['CURRENT_STATE'];

	if (isset($arFilterValues['GROUP_ID']) && $arFilterValues['GROUP_ID'] > 0)
	{
		$arResult['FILTER']['GROUP_ID'] = $arFilter['GROUP_ID'] = intval($arFilterValues['GROUP_ID']);
	}

	if (isset($arFilterValues['OWNER_ID']) && intval($arFilterValues['OWNER_ID']) > 0)
		$arResult['FILTER']['OWNER_ID'] = $arFilter['OWNER_ID'] = intval($arFilterValues['OWNER_ID']);
	if (isset($arFilterValues['MEMBER_ID']) && intval($arFilterValues['MEMBER_ID']) > 0)
		$arResult['FILTER']['MEMBER_ID'] = $arFilter['MEMBER_ID'] = intval($arFilterValues['MEMBER_ID']);
}

$arResult['MEETING_ROOMS_LIST'] = array();
if ($arParams['RESERVE_MEETING_IBLOCK_ID'] || $arParams['RESERVE_VMEETING_IBLOCK_ID'])
{
	$dbMeetingsList = CIBlockSection::GetList(
		array('IBLOCK_ID' => 'ASC', 'NAME' => 'ASC', 'ID' => 'DESC'),
		array('IBLOCK_ID' =>
			array(intval($arParams['RESERVE_MEETING_IBLOCK_ID']), intval($arParams['RESERVE_VMEETING_IBLOCK_ID']))
		),
		false,
		array('ID', 'IBLOCK_ID', 'NAME', 'DESCRIPTION')
	);
	while ($arRoom = $dbMeetingsList->GetNext())
	{
		$arResult['MEETING_ROOMS_LIST'][CMeeting::MakePlace($arRoom["IBLOCK_ID"], $arRoom["ID"])] = $arRoom;
	}
}

$dbRes = CMeeting::GetList(
	array("ID" => 'DESC'),
	$arFilter,
	false,
	$arNavParams,
	array('ID', 'TITLE', 'CURRENT_STATE', 'DATE_START', 'OWNER_ID', 'PLACE')
);

$arResult["NAV_STRING"] = $dbRes->GetPageNavStringEx($navComponentObject = null, $arParams["PAGER_TITLE"]);

while ($arRes = $dbRes->GetNext())
{
	$arRes['URL'] = str_replace('#MEETING_ID#', $arRes['ID'], $arParams['MEETING_URL']);
	$arRes['URL_EDIT'] = str_replace('#MEETING_ID#', $arRes['ID'], $arParams['MEETING_EDIT_URL']);
	$arRes['URL_COPY'] = str_replace('#MEETING_ID#', $arRes['ID'], $arParams['MEETING_COPY_URL']);

	$arRes['USERS'] = CMeeting::GetUsers($arRes['ID']);
	foreach ($arRes['USERS'] as $u=>$r)
	{
		if ($r == CMeeting::ROLE_OWNER)
			$arRes['OWNER_ID'] = $u;
	}
	$arUserIDs = array_merge($arUserIDs, array_keys($arRes['USERS']));

	if(strlen($arRes['PLACE'])>0 && array_key_exists($arRes['PLACE'], $arResult['MEETING_ROOMS_LIST']))
	{
		$arRes['PLACE'] = $arResult['MEETING_ROOMS_LIST'][$arRes['PLACE']]['NAME'];
	}

	$arResult['MEETINGS'][] = $arRes;
}

$title = GetMessage('ML_LIST_TITLE'.($arParams['USER_ID'] == $USER->GetID() ? '' : '_NOT_MINE'));
$APPLICATION->SetTitle($title);
if ($arParams['SET_NAVCHAIN'] !== 'N')
	$APPLICATION->AddChainItem($title, $arParams['LIST_URL']);

CJSCore::Init(array('meeting', 'popup', 'ajax'));

$this->IncludeComponentTemplate();
?>