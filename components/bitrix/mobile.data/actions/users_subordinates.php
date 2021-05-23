<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

//cache data
$cache = new CPHPCache();
$cache_time = 3600 * 24 * 365;
$detailurl = $_REQUEST["detail_url"];
$cache_path = '/mobile_cache/' . $action;
$data = null;
$action = $_REQUEST["mobile_action"];
$arStructure = array();
$arSections = array();
$curUserId = (int)$GLOBALS["USER"]->GetID();

CModule::IncludeModule('tasks');

$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);
$arSubDeps = CTasks::GetSubordinateDeps();

$withTags = ($_REQUEST["tags"] == "N" ? "N" : "Y");
$cache_id = "mobileAction|get_subordinated_user_list|"
	. $curUserId . "|" . $detailurl
	. "|" . $withTags
	. '|' . md5(serialize($arSubDeps))
	. '|' . time()
	. '|' . CSite::GetNameFormat(false);

if ($cache->InitCache($cache_time, $cache_id, $cache_path))
{
	$cachedData = $cache->GetVars();
	$data = $cachedData["DATA"];
	$tableType = $cachedData["TYPE"];
}
else
{
	$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
	$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U" . $curUserId);
	$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD");
	$GLOBALS["CACHE_MANAGER"]->RegisterTag("iblock_id_" . $IBlockID);

	$tmpData = array(
		"NAME" => GetMessage("MD_EMPLOYEES_ALL"),
		"ID" => 0,
		"OUTSECTION" => true
	);

	if (SITE_CHARSET != "utf-8")
	{
		$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");
	}

	$data = Array(
		$tmpData
	);
	$filter = array(
		"ACTIVE" => "Y"
	);

	/*
	if (IsModuleInstalled('bitrix24'))
		$filter["!LAST_ACTIVITY"] = false;
	*/

	$filter['UF_DEPARTMENT'] = $arSubDeps;

	$arParams = Array("FIELDS" => Array("NAME", "ID", "PERSONAL_PHOTO", "LAST_NAME", "WORK_POSITION"));
	if ($withTags == "Y")
	{
		$arDepartaments = array();
		$arSectionFilter = array(
			'IBLOCK_ID' => $IBlockID,
			'ID' => $arSubDeps
		);

		$dbRes = CIBlockSection::GetList(
			array('LEFT_MARGIN' => 'DESC'),
			$arSectionFilter,
			false,
			array('ID', 'NAME')
		);

		while ($arRes = $dbRes->Fetch())
			$arDepartaments[$arRes["ID"]] = $arRes["NAME"];
		$arParams["SELECT"] = Array("UF_DEPARTMENT");
	}

	$dbUsers = CUser::GetList(
		($by = array("last_name" => "asc", "name" => "asc")),
		($order = false),
		$filter,
		$arParams
	);

	while ($userData = $dbUsers->Fetch())
	{
		if (((int)$userData['ID']) === $curUserId)
		{
			continue;
		}    // skip myself

		if (intval($userData["PERSONAL_PHOTO"]) > 0)
		{
			$arImage = CFile::ResizeImageGet(
				$userData["PERSONAL_PHOTO"],
				array("width" => 64, "height" => 64),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			$img_src = $arImage["src"];
		}
		else
		{
			$img_src = false;
		}

		$tmpData = Array(
			"NAME" => CUser::FormatName(CSite::GetNameFormat(false), $userData, true),
			"ID" => $userData["ID"],
			"IMAGE" => $img_src,
			"URL" => $detailurl . $userData["ID"],
			"TAGS" => ""
		);
		if ($withTags == "Y")
		{
			$tmpTags = Array($userData["WORK_POSITION"]);
			for ($i = 0; $i < count($userData["UF_DEPARTMENT"]); $i++)
			{
				$tmpTags[] = $arDepartaments[$userData["UF_DEPARTMENT"][$i]];
			}
			$tmpData["TAGS"] = implode(",", $tmpTags);
		}

		if (SITE_CHARSET != "utf-8")
		{
			$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");
		}
		$data[] = $tmpData;
	}

	$GLOBALS["CACHE_MANAGER"]->EndTagCache();

	$tableType = "a_users";

	if ($cache->StartDataCache())
	{
		$cache->EndDataCache(
			array(
				"DATA" => $data,
				"TYPE" => $tableType
			)
		);
	}
}
$tableTitle = GetMessage("MD_EMPLOYEES_TITLE");
$tableData = AddTableData($tableData, $data, $tableTitle, $tableType);

return $tableData;
					