<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $USER;

$bFound = false;
$data = array('data' => array('a_users' => array()));

if (CModule::IncludeModule("socialnetwork"))
{
	$arConvertRes = CSocNetLogTools::GetDataFromRatingEntity($_REQUEST["RATING_VOTE_TYPE_ID"], $_REQUEST["RATING_VOTE_ENTITY_ID"]);
	if (
		is_array($arConvertRes)
		&& $arConvertRes["LOG_ID"] > 0
	)
		$bFound = true;
}

if ($bFound)
{
	$detailurl = str_replace("(_)", "#", $_REQUEST["URL"]);

	$ar = Array(
		"ENTITY_TYPE_ID" => $_REQUEST["RATING_VOTE_TYPE_ID"],
		"ENTITY_ID" => intval($_REQUEST["RATING_VOTE_ENTITY_ID"]),
		"LIST_LIMIT" => 60,
		"LIST_TYPE" => (isset($_REQUEST["RATING_VOTE_LIST_TYPE"]) && $_REQUEST["RATING_VOTE_LIST_TYPE"] == "minus" ? "minus" : "plus"),
		"USER_FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION"),
		"USER_SELECT" => array("UF_DEPARTMENT")
	);
	$arVoteList = CRatings::GetRatingVoteList($ar);
	$arDepartaments = Array();

	if (CModule::IncludeModule("iblock"))
	{
		$arSectionFilter = array(
			"IBLOCK_ID" => COption::GetOptionInt('intranet', 'iblock_structure', 0)
		);

		$dbRes = CIBlockSection::GetList(
			array("LEFT_MARGIN" => "DESC"),
			$arSectionFilter,
			false,
			array("ID", "NAME")
		);

		while ($arRes = $dbRes->Fetch())
			$arDepartaments[$arRes["ID"]] = $arRes["NAME"];
	}

	$arResultData = array();

	foreach ($arVoteList["items"] as $arVoter)
	{
		if (intval($arVoter["PERSONAL_PHOTO"]) > 0)
		{
			$arImage = CFile::ResizeImageGet(
				$arVoter["PERSONAL_PHOTO"],
				array("width" => 64, "height" => 64),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			$img_src = $arImage["src"];
		}
		else
			$img_src = false;

		$arTags = array();

		if (
			array_key_exists("WORK_POSITION", $arVoter)
			&& strlen($arVoter["WORK_POSITION"]) > 0
		)
			$arTags[] = $arVoter["WORK_POSITION"];

		if (
			array_key_exists("UF_DEPARTMENT", $arVoter)
			&& is_array($arVoter["UF_DEPARTMENT"])
			&& count($arVoter["UF_DEPARTMENT"]) > 0
		)
			foreach($arVoter["UF_DEPARTMENT"] as $user_department)
				if (array_key_exists($user_department, $arDepartaments))
					$arTags[] = $arDepartaments[$user_department];

		$tmpVoter = Array(
			"NAME" => $arVoter["NAME"],
			"LAST_NAME" => $arVoter["LAST_NAME"],
			"SECOND_NAME" => $arVoter["SECOND_NAME"],
			"LOGIN" => $arVoter["LOGIN"]
		);

		$tmpData = Array(
			"NAME" => CUser::FormatName(CSite::GetNameFormat(false), $tmpVoter, true, false),
			"ID" => $arVoter["ID"],
			"IMAGE" => $img_src,
			"URL" => (
				(
					strpos($detailurl, "#user_id#") !== false
					|| strpos($detailurl, "#USER_ID#") !== false
				)
					? str_replace(array("#user_id#", "#USER_ID#"), $arVoter["ID"], $detailurl)
					: $detailurl.$arVoter["ID"]
			)
		);

		if (count($arTags) > 0)
			$tmpData["TAGS"] = implode(",", $arTags);

		if (SITE_CHARSET != "utf-8")
			$tmpData = $GLOBALS["APPLICATION"]->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");

		$arResultData[] = $tmpData;
	}

	if ($USER->IsAuthorized())
	{
		$event = new \Bitrix\Main\Event(
			'main',
			'onRatingListViewed',
			array(
				'entityTypeId' => $_REQUEST['RATING_VOTE_TYPE_ID'],
				'entityId' => $_REQUEST['RATING_VOTE_ENTITY_ID'],
				'userId' => $USER->getId()
			)
		);
		$event->send();
	}

	if (count($arResultData) > 0)
		$data = AddTableData(array(), $arResultData, "xxx", "a_users");
}

return $data;
?>