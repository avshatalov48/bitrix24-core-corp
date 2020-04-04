<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
/************** CACHE **********************************************/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$arVoteList = array(
	"answer_id" => $_REQUEST["answer_id"],
	"nPageSize" => 20, //$_REQUEST["nPageSize"],
	"iNumPage" => 0, //$_REQUEST["iNumPage"],
	"bShowAll" => true, //$_REQUEST["bShowAll"],
	"items" => array(),
	"StatusPage" => "done",
	"url" => "/mobile/users/?user_id=#user_id#"
);
$arUsers = array();
if ($_REQUEST["answer_id"] > 0 && check_bitrix_sessid())
{
	$arParams["CACHE_TIME"] = 600;
	global $CACHE_MANAGER;
	$cache = new CPHPCache();
	$cache_id = "vote_user_list_".md5(serialize($arVoteList));
	$cache_path = $CACHE_MANAGER->GetCompCachePath(CComponentEngine::MakeComponentPath("voting.current"));
	$res = (($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path)) ? $cache->GetVars() : array());
	if (is_array($res) && !empty($res))
		$arVoteList = $res;
	else if (CModule::IncludeModule("vote"))
	{
		$arEventsInfo = array();
		$db_res = CVoteEvent::GetUserAnswerStat(array(),
			array("ANSWER_ID" => $arVoteList["answer_id"], "VALID" => "Y", "bGetVoters" => "Y", "bGetMemoStat" => "N"),
			array(
				"nPageSize" => $arVoteList["nPageSize"],
				"bShowAll" => $arVoteList["bShowAll"],
				"iNumPage" => ($arVoteList["iNumPage"] > 0 ? $arVoteList["iNumPage"] : false)
			)
		);
		if ($db_res && ($res = $db_res->Fetch()))
		{
			$arEventsInfo = $res;
			$arVoteList["StatusPage"] = (($db_res->bShowAll || $db_res->NavPageNomer >= $db_res->NavPageCount ||
				$arVoteList["nPageSize"] > $db_res->NavRecordCount) ? "done" : "continue");
			if ($arVoteList["iNumPage"] > 0 && $arVoteList["iNumPage"] > $db_res->NavPageCount)
				$arVoteList["StatusPage"] = "done";
			else {
				do {
					$arUsers[] = $res["AUTH_USER_ID"];
				} while ($res = $db_res->Fetch());
			}
		}

		if (!empty($arUsers))
		{
			$arSelect = Array("FIELDS" => Array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION"));
			$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', 0);
			$arDepartaments = array();
			if ($iblockId > 0)
			{
				$arSectionFilter = array( 'IBLOCK_ID' => $iblockId);

				$dbRes = CIBlockSection::GetList(
					array('LEFT_MARGIN' => 'DESC'),
					$arSectionFilter,
					false,
					array('ID', 'NAME')
				);

				while ($arRes = $dbRes->Fetch())
					$arDepartaments[$arRes["ID"]] = trim($arRes["NAME"]);
				$arSelect["SELECT"] = Array("UF_DEPARTMENT");
			}

			$db_res = CUser::GetList(
				($by = "ID"),
				($order = "ASC"),
				array("ID" => implode("|", $arUsers)),
				$arSelect
			);
			$arUsersData = array();
			while ($res = $db_res->Fetch())
			{
				$img_src = "";
				if (array_key_exists("PERSONAL_PHOTO", $res))
				{
					$arFileTmp = CFile::ResizeImageGet(
						$res["PERSONAL_PHOTO"],
						array("width" => 64, "height" => 64),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					if (!!$arFileTmp)
						$img_src = $arFileTmp["src"];
				}
				$tmpData = Array(
					"NAME" =>  CUser::FormatName(CSite::GetNameFormat(false), $res, true, false),
					"ID" => $res["ID"],
					"IMAGE" => $img_src,
					"TAGS" => "",
					"WORK_POSITION" => $res["WORK_POSITION"],
					"WORK_DEPARTMENTS" => array(),
					"URL" => CComponentEngine::MakePathFromTemplate($arVoteList["url"], array("UID" => $res["ID"], "user_id" => $res["ID"], "USER_ID" => $res["ID"]))
				);
				if ($iblockId > 0)
				{
					$arUserDepartments = array();
					if (is_array($res['UF_DEPARTMENT']))
						foreach ($res['UF_DEPARTMENT'] as $departmentId)
							$arUserDepartments[] = $arDepartaments[$departmentId];
					$tmpTags = array_merge(
						array(trim($res['WORK_POSITION'])),
						$arUserDepartments
					);

					$tmpData["TAGS"] = implode(",", $tmpTags);
					$tmpData['WORK_DEPARTMENTS'] = $arUserDepartments;
				}
				$arUsersData[$res["ID"]] = $tmpData;
			}


			$arVoteList["items"] = array();
			foreach($arUsers as $id)
				$arVoteList["items"][$id.""] = $arUsersData[$id];
			if ($arParams["CACHE_TIME"] > 0):
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
				if (!!$arEventsInfo){
					$db_res = CVoteQuestion::GetByID($arEventsInfo["QUESTION_ID"]);
					if ($db_res && ($res = $db_res->Fetch())){
						CVoteCacheManager::SetTag($cache_path, "V", $res["VOTE_ID"]);
					}
				}
				$cache->EndDataCache($arVoteList);
			endif;
		}
	}
}
if (!function_exists("AddTableData"))
{
	function AddTableData($source = array(), $data = array(), $data_name = "", $dataID = false)
	{
		if ($dataID === false)
			$dataID = 'data' . rand(1, 100000);

		$source['data'][$dataID] = $data;

		$source['names'][$dataID] = (string) $data_name;

		return ($source);
	}
}
$data = array();
if (!empty($arVoteList["items"]))
{
	$arVoteList["items"] = array_values($arVoteList["items"]);
	$data = AddTableData(array(), $arVoteList["items"], "voting.current", "voters");
}


$APPLICATION->RestartBuffer();
if (SITE_CHARSET != "utf-8")
{
	$data = $APPLICATION->ConvertCharsetArray($data, SITE_CHARSET, "utf-8");
}
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo json_encode($data);
define('PUBLIC_AJAX_MODE', true);
CMain::FinalActions();
die();