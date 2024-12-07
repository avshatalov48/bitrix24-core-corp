<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
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
$arStructure = [];
$arSections = [];
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

	$tmpData = [
		"NAME" => GetMessage("MD_EMPLOYEES_ALL"),
		"ID" => 0,
		"OUTSECTION" => true,
	];

	$data = [$tmpData];
	$filter = ["ACTIVE" => "Y"];

	/*
	if (IsModuleInstalled('bitrix24'))
		$filter["!LAST_ACTIVITY"] = false;
	*/

	$filter['UF_DEPARTMENT'] = $arSubDeps;

	$arParams = [
		"FIELDS" => [
			"NAME",
			"ID",
			"PERSONAL_PHOTO",
			"LAST_NAME",
			"WORK_POSITION",
		],
	];
	if ($withTags === "Y")
	{
		$arDepartaments = [];
		$arSectionFilter = [
			'IBLOCK_ID' => $IBlockID,
			'ID' => $arSubDeps,
		];

		$dbRes = CIBlockSection::GetList(
			['LEFT_MARGIN' => 'DESC'],
			$arSectionFilter,
			false,
			['ID', 'NAME']
		);

		while ($arRes = $dbRes->Fetch())
			$arDepartaments[$arRes["ID"]] = $arRes["NAME"];
		$arParams["SELECT"] = ["UF_DEPARTMENT"];
	}

	$dbUsers = CUser::GetList(
		[
			"last_name" => "asc",
			"name" => "asc",
		],
		'',
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
				[
					"width" => 64,
					"height" => 64,
				],
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

		$tmpData = [
			"NAME" => CUser::FormatName(CSite::GetNameFormat(false), $userData, true),
			"ID" => $userData["ID"],
			"IMAGE" => $img_src,
			"URL" => $detailurl . $userData["ID"],
			"TAGS" => "",
		];
		if ($withTags === "Y")
		{
			$tmpTags = [$userData["WORK_POSITION"]];
			$departmentCount = count($userData["UF_DEPARTMENT"]);
			for ($i = 0; $i < $departmentCount; $i++)
			{
				$tmpTags[] = $arDepartaments[$userData["UF_DEPARTMENT"][$i]];
			}
			$tmpData["TAGS"] = implode(",", $tmpTags);
		}

		$data[] = $tmpData;
	}

	$GLOBALS["CACHE_MANAGER"]->EndTagCache();

	$tableType = "a_users";

	if ($cache->StartDataCache())
	{
		$cache->EndDataCache(
			[
				"DATA" => $data,
				"TYPE" => $tableType,
			]
		);
	}
}
$tableTitle = GetMessage("MD_EMPLOYEES_TITLE");
$tableData = AddTableData($tableData, $data, $tableTitle, $tableType);

return $tableData;
