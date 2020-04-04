<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("meeting"))
{
	ShowError(GetMessage("MEETING_MODULE_NOT_FOUND"));
	return;
}

$arParams["MEETING_URL_TPL"] = isset($arParams["MEETING_URL_TPL"]) ? $arParams["MEETING_URL_TPL"] : '/services/meeting/meeting/#MEETING_ID#/';
$arParams['CALLBACK_NAME'] = isset($arParams['CALLBACK_NAME']) ? $arParams['CALLBACK_NAME'] : '';

$skip = $arParams['MEETING_ID'];

$arOrder = array("DATE_START" => "DESC", "TITLE" => "ASC", "ID" => "DESC");
$arFilter = array(
	'USER_ID' => $USER->GetID()
);

if($skip > 0)
	$arFilter['!ID'] = intval($skip);

if ($_REQUEST['mode'] == 'selector_search')
	CUtil::JSPostUnEscape();

if ($_REQUEST['FILTER'])
{
	$arFilterValues = $_REQUEST['FILTER'];
	if (isset($arFilterValues['TITLE']) && strlen(trim($arFilterValues['TITLE'])) > 0)
		$arFilter['~TITLE'] = '%'.trim($arFilterValues['TITLE']).'%';

	if (isset($arFilterValues['CURRENT_STATE']) && strlen($arFilterValues['CURRENT_STATE'])==1 && in_array($arFilterValues['CURRENT_STATE'], array(CMeeting::STATE_PREPARE, CMeeting::STATE_ACTION, CMeeting::STATE_CLOSED)))
		$arFilter['CURRENT_STATE'] = $arFilterValues['CURRENT_STATE'];

	if (isset($arFilterValues['OWNER_ID']) && intval($arFilterValues['OWNER_ID']) > 0)
		$arFilter['OWNER_ID'] = intval($arFilterValues['OWNER_ID']);
}

$dbRes = CMeeting::GetList($arOrder, $arFilter, false, array('nTopCount' => 10));
$arResult['MEETINGS'] = array();
while ($arMeeting = $dbRes->GetNext())
{
	$arMeeting['URL'] = str_replace(
		array('#MEETING_ID#', '#ID#'),
		$arMeeting['ID'],
		$arParams["MEETING_URL_TPL"]
	);

	$arResult['MEETINGS'][] = $arMeeting;
}

if ($_REQUEST['mode'] == 'selector_search'):
	$APPLICATION->RestartBuffer();
	echo CUtil::PhpToJsObject($arResult['MEETINGS'], false, true);
	die();
else:
	CJSCore::Init(array('meeting'));
	$this->IncludeComponentTemplate();
endif;
?>