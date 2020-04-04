<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("meeting")||!CModule::IncludeModule("iblock"))
	return;


__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID.'/'.basename(__FILE__));

$arFields = array(
	'TITLE' => GetMessage('MEETING_TITLE'),
	'DATE_START' => ConvertTimeStamp(time() + 86400),
	'DURATION' => 3600,
	'DESCRIPTION' => GetMessage('MEETING_DESCRIPTION'),
	'PLACE' => GetMessage('MEETING_PLACE'),
	'USERS' => array(
		1 => CMeeting::ROLE_OWNER,
		477 => CMeeting::ROLE_KEEPER,
		11 => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
		rand(12,476) => CMeeting::ROLE_MEMBER,
	)
);

$MEETING_ID = CMeeting::Add($arFields);

$arResponsible = array(1 => 1, 11, $arFields['USERS'][rand(3,9)]);
for ($i = 1; $i <= 3; $i++)
{
	CMeetingItem::Add(array(
		'MEETING_ID' => $MEETING_ID,
		'TITLE' => GetMessage('MEETING_ITEM_TITLE_'.$i),
		'SORT' => 100*$i,
		'RESPONSIBLE' => $arResponsible[$i]
	));
}
/*
	$iblockCode = "calendar_employees";
	$iblockType = "events";

	$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		CMeeting::AddEvent($MEETING_ID, $arFields, array("CALENDAR_IBLOCK_ID" => $arIBlock['ID']));
	}
*/

?>