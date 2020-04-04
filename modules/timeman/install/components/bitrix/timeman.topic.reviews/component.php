<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
$arParams["FORUM_ID"] = intVal(COption::GetOptionInt("timeman","report_forum_id",""));
$arParams["REPORT_ID"] = intVal($arParams["REPORT_ID"]);
$arParams["ENTRY_ID"] = intVal($arParams["ENTRY_ID"]);

$arResult["COMMENTS"] = Array();
$user_url = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id']);

if ($arParams["FORUM_ID"])
{
	$FORUM_TOPIC_ID = 0;

	if ($arParams["REPORT_ID"])
	{
		$dbReport = CTimeManReportFull::GetByID($arParams["REPORT_ID"]);
		$arReport = $dbReport->Fetch();
		$FORUM_TOPIC_ID = $arReport["FORUM_TOPIC_ID"];
	}
	else if ($arParams['ENTRY_ID'])
	{
		$dbRes = CTimeManEntry::GetByID($arParams['ENTRY_ID']);
		$arEntry = $dbRes->Fetch();
		$FORUM_TOPIC_ID = $arEntry["FORUM_TOPIC_ID"];
	}

	if ($FORUM_TOPIC_ID > 0)
	{
		CModule::IncludeModule("forum");
		$parser = new forumTextParser(LANGUAGE_ID);
		$allow = forumTextParser::GetFeatures(CForumNew::GetByID($arParams["FORUM_ID"]));
		$db_res = CForumMessage::GetList(array("ID"=>"ASC"), array("TOPIC_ID"=>$FORUM_TOPIC_ID));
		while ($ar_res = $db_res->Fetch())
		{
			$dbAuthor = CUser::GetByID($ar_res["AUTHOR_ID"]);
			$arAuthor = $dbAuthor->Fetch();
			$ar_res["AUTHOR_PHOTO"] =$arAuthor['PERSONAL_PHOTO'] > 0
				? CIntranetUtils::InitImage($arAuthor['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
				: array();
			$ar_res["AUTHOR_URL"] = str_replace(array('#ID#', '#USER_ID#'), $ar_res["AUTHOR_ID"], $user_url);
			$ar_res["POST_MESSAGE_HTML"] = $parser->convert(
				(COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $ar_res["POST_MESSAGE_FILTER"] : $ar_res["POST_MESSAGE"]),
				$allow,
				"html");
			$arResult["COMMENTS"][] = $ar_res;
		}
	}

	$this->IncludeComponentTemplate();
}

// *****************************************************************************************
// *****************************************************************************************
?>