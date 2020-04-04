<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
header("Content-Type: application/x-javascript");

if(!(isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized()))
{
	$APPLICATION->RestartBuffer();
	exit(json_encode(array('status' => 'failed')));
}
if(!CModule::IncludeModule('webdav')) //|| !check_bitrix_sessid()
{
	$APPLICATION->RestartBuffer();
	exit(json_encode(array('status' => 'failed')));
}

$compPath = $this->getPath();

function CheckStrCharsetForJson($str)
{
	global $APPLICATION;
	if (ToUpper(SITE_CHARSET) !== 'UTF-8')
	{
		$str = $APPLICATION->ConvertCharsetArray($str, SITE_CHARSET, 'utf-8');
	}
	return $str;
}

function GetRequestTupe($arParams)
{
	$pageQ = $_SERVER['REQUEST_URI'];
	if ($arParsedUrl = parse_url(CWebDavBase::get_request_url()))
	{
		$pageQ = $arParsedUrl['path'];
	}
	$pageQ = str_replace($arParams["SEF_FOLDER"], "", $pageQ);
	$arU0 = explode("/",$pageQ);
	$arU = array();
	foreach($arU0 as $v)
	{
		if(strlen($v) > 0)
		{
			$arU[] = $v;
		}
	}
	return $arU;
}

function GetTableSettings($foldersNum, $filesNum)
{
	$sFF = GetMessage("MOBILE_MODULE_FOLDERS_FILES");
	$sFF = str_replace("#folders#", ($foldersNum > 0 ? $foldersNum : "0"), $sFF);
	$sFF = str_replace("#files#", ($filesNum > 0 ? $filesNum : "0"), $sFF);
	$res = array(
		"footer" => CheckStrCharsetForJson($sFF),
		//"searchField" => (($foldersNum+$filesNum) > 0 ? "YES" : "NO"),
	);
	return $res;
}

function GetFileModificationDate($arF)
{
	$iTSTS = 0;
	$fTSTS = 0;
	$iTSD = GetTime(mktime(0, 0, 0, 01, 01, 2000));
	$fTSD = GetTime(mktime(0, 0, 0, 01, 01, 2000));
	if(isset($arF["TIMESTAMP_X"]))
	{
		$iTSTS = MakeTimeStamp($arF["TIMESTAMP_X"]);
		$iTSD = $arF["TIMESTAMP_X"];
	}
	if(isset($arF["FILE"]["TIMESTAMP_X"]))
	{
		$fTSTS = MakeTimeStamp($arF["FILE"]["TIMESTAMP_X"]);
		$fTSD = $arF["FILE"]["TIMESTAMP_X"];
	}
	return ($iTSTS > $fTSTS ? $iTSD : $fTSD);
}

function GetFilrIcon($compPath, $pathQ, $arParams, $arF)
{
	$arMime = array(
		'pdf' => 'pdf.png',
		'doc' => 'doc.png',
		'docx' => 'doc.png',
		'ppt' => 'ppt.png',
		'pptx' => 'ppt.png',
		'rar' => 'rar.png',
		'xls' => 'xls.png',
		'xlsx' => 'xls.png',
		'zip' => 'zip.png',
	);

	$fIcon = $compPath . "/images/" . "blank.png";
	$fExtQ = strtolower(GetFileExtension($pathQ));

	if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
	{
		if(CFile::isImage($arF['NAME']))
		{
			return $compPath . "/images/img.png";
		}
		$icon = isset($arMime[$fExtQ])? $arMime[$fExtQ] : 'blank.png';
		return $compPath . "/images/{$icon}";
	}

	$fileID = $pathQ;
	$arFile = CFile::MakeFileArray($fileID);
	$isPictureExt = false;
	$arPExt = explode(",", CFile::GetImageExtensions());
	foreach($arPExt as $v)
	{
		if(strtolower(trim($v)) == $fExtQ)
		{
			$isPictureExt=true;
			break;
		}
	}

	$isPicture = false;
	if($isPictureExt && isset($arFile["tmp_name"]))
	{
		$imgArray = CFile::GetImageSize($arFile["tmp_name"], true);
		if(is_array($imgArray))
		{
			if(
				$arFIcon = CFile::ResizeImageGet(
					$fileID,
					array("width" => "58", "height" =>"58"),
					BX_RESIZE_IMAGE_EXACT,
					true
				)
			)
			{
				$fIcon = $arFIcon["src"];
				$isPicture = true;
			}
		}
	}

	if(!$isPicture && array_key_exists($fExtQ , $arMime))
	{
		$fIcon = $compPath . "/images/" . $arMime[$fExtQ];
	}
	return $fIcon;
}

$arURL = GetRequestTupe($arParams);
$requestTupe = array_shift($arURL);

$arParams["RESOURCE_TYPE"] = "IBLOCK";
$arParams["IBLOCK_TYPE"] = "library";
$sID = CSite::GetDefSite();
if( $requestTupe == "shared")
{
	$shared_files = COption::GetOptionString("webdav", "shared_files", null);
	if($shared_files == null)
	{
		$APPLICATION->RestartBuffer();
		exit(json_encode(array('status' => 'failed')));
	}
	$shared_files = unserialize($shared_files);
	$arParams["IBLOCK_ID"] = $shared_files[$sID]["id"];
	$arParams["BASE_URL"] = $arParams["SEF_FOLDER"] . "/" . $requestTupe . "/";
}
elseif( $requestTupe == "group")
{
	$group_files = COption::GetOptionString("webdav", "group_files", null);
	if($group_files == null)
	{
		$APPLICATION->RestartBuffer();
		exit(json_encode(array('status' => 'failed')));
	}
	$group_files = unserialize($group_files);
	$arParams["IBLOCK_ID"] = $group_files[$sID]["id"];
	$filesOwnerGroupID = array_shift($arURL);
	if($filesOwnerGroupID != null && intval($filesOwnerGroupID) > 0)
	{
		$arParams["BASE_URL"] = $arParams["SEF_FOLDER"] . "/" . $requestTupe . "/" . $filesOwnerGroupID . "/";
		$rootSectionID = CIBlockWebdavSocnet::GetSectionID($arParams["IBLOCK_ID"], "group", $filesOwnerGroupID);
		if(intval($rootSectionID) <= 0)
		{
			$APPLICATION->RestartBuffer();

			echo json_encode(array( "data" => array(), "TABLE_SETTINGS" => GetTableSettings(0, 0)));
			die();
		}

		$arParams["ROOT_SECTION_ID"] = $rootSectionID;
	}
	else
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			$APPLICATION->RestartBuffer();
			exit(json_encode(array('status' => 'failed')));
		}
		$arParams["BASE_URL"] = $arParams["SEF_FOLDER"] . "/" . $requestTupe . "/";

		$arGroupFilterMy = array(
			"USER_ID" => $GLOBALS["USER"]->GetID(),
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_SITE_ID" => $sID,
			"GROUP_ACTIVE" => "Y"
		);


		$dbGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arGroupFilterMy,
			false,
			false,
			array("GROUP_ID","GROUP_NAME")
		);

		$foldersNum = 0;
		$arResFiles = array();
		while ($arG = $dbGroups->Fetch())
		{
			$arResFiles[] = array(
				"NAME" => CheckStrCharsetForJson($arG["GROUP_NAME"]),
				"TABLE_URL" => $arParams["BASE_URL"] . $arG["GROUP_ID"] . "/",
				"IMAGE" => $compPath . "/images/folder.png",
				"TABLE_SETTINGS" => array(
					//"name" => $nameQ,
					"type" => "files",
					"useTagsInSearch" => "NO",
				),
			);
			$foldersNum++;
		}
		$APPLICATION->RestartBuffer();
		echo json_encode(array( "data" => $arResFiles, "TABLE_SETTINGS" => GetTableSettings($foldersNum, 0)));
		die();
	}
}
elseif( $requestTupe == "user")
{
	$user_files = COption::GetOptionString("webdav", "user_files", null);
	if($user_files == null)
	{
		$APPLICATION->RestartBuffer();
		exit(json_encode(array('status' => 'failed')));
	}
	$user_files = unserialize($user_files);
	$arParams["IBLOCK_ID"] = $user_files[$sID]["id"];
	$filesOwnerUserID = array_shift($arURL);
	$arParams["BASE_URL"] = $arParams["SEF_FOLDER"] . "/" . $requestTupe . "/" . $filesOwnerUserID . "/";

	$rootSectionID = CIBlockWebdavSocnet::GetSectionID($arParams["IBLOCK_ID"], "user", $filesOwnerUserID);
	if(intval($rootSectionID) <= 0)
	{
		$APPLICATION->RestartBuffer();
		echo json_encode(array( "data" => array(), "TABLE_SETTINGS" => GetTableSettings(0, 0)));
		die();
	}

	$arParams["ROOT_SECTION_ID"] = $rootSectionID;

}
else
{
	$APPLICATION->RestartBuffer();
	exit(json_encode(array('status' => 'failed')));
}

$action = array_key_exists("action", $_REQUEST) ? $_REQUEST['action'] : "";

$lng = isset($_REQUEST['lang'])? trim($_REQUEST['lang']): '';
$lng = substr(preg_replace('/[^a-z0-9_]/i', '', $lng), 0, 2);

if ( ! defined('LANGUAGE_ID') )
{
	$rsSite = CSite::GetByID(SITE_ID);
	if ($arSite = $rsSite->Fetch())
		define('LANGUAGE_ID', $arSite['LANGUAGE_ID']);
	else
		define('LANGUAGE_ID', 'en');
}

$langFilename = dirname(__FILE__) . '/lang/' . $lng . '/ajax.php';
if (file_exists($langFilename))
{
	__IncludeLang($langFilename);
}

if (CModule::IncludeModule('compression'))
{
	CCompress::Disable2048Spaces();
}


session_write_close();

$arParams["USE_AUTH"] = "Y";
$baseURL = $arParams["BASE_URL"];
$arParams["BASE_URL"] = ($APPLICATION->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$baseURL."/");

$arParams["NOT_SAVE_SUG_FILES"] = true;

if ($arParams["RESOURCE_TYPE"] == "FOLDER")
{
	$ob = new CWebDavFile($arParams, $baseURL);
}
else
{
	$ob = new CWebDavIblock(intval($arParams['IBLOCK_ID']), $baseURL, $arParams);
	if($requestTupe == "user" && !empty($filesOwnerUserID))
	{
		$ob->attributes['user_id'] = $filesOwnerUserID;
	}
}


if($ob->IsDir())
{
	$arResFiles = array();
	$optionsQ = array (
		'path' => $ob->_path,
		'depth' => 1,
		'check_permissions' => true,
	);
	$arSelectedFieldsQ = array('NAME','FILE_TUPES');
	$filesQ = null;
	$arFilterQ = array();
	$resQ = $ob->PROPFIND(
		$optionsQ,
		$filesQ,
		array(
			"FILTER" => $arFilterQ,
			"COLUMNS" => $arSelectedFieldsQ,
			"return" => "nav_result",
			"get_clones" => "Y",
			'NON_TRASH_SECTION' => true,
			'NON_OLD_DROPPED_SECTION' => true,
		)
	);
	$foldersNum = 0;
	$filesNum = 0;
	if(is_array($resQ) && array_key_exists("NAV_RESULT", $resQ))
	{
		while ($arF = $resQ["NAV_RESULT"]->Fetch())
		{
			$nameQ = CheckStrCharsetForJson($arF["NAME"]);
			$pathQ = CheckStrCharsetForJson($arF["PATH"]);
			if($arF["TYPE"] == "S")
			{
				$arResFiles[] = array(
					"NAME" => $nameQ,
					"TABLE_URL" => $ob->base_url . $pathQ,
					"IMAGE" => $compPath . "/images/folder.png",
					"TABLE_SETTINGS" => array(
						//"name" => $nameQ,
						"type" => "files",
						"useTagsInSearch" => "NO",
					),
				);
				$foldersNum++;
			}
			else
			{
				$mime = "";
				$fSize = "";
				$fDateCreate = "";
				if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
				{
					$fSize = CFile::FormatSize($arF["PROPERTY_WEBDAV_SIZE_VALUE"]);
					$fDateCreate = $arF["DATE_CREATE"];
				}
				$fIcon = GetFilrIcon($compPath, ($ob->base_url . $pathQ), $arParams, $arF);

				$arQQ = array(
					"VALUE" => $arF["ID"],
					"NAME" => $nameQ,
					"URL" => array(
						"URL" => $ob->base_url . $pathQ,
						"EXTERNAL" => "YES",
					),
//					"ACCESSORY_URL" =>  array(
//						"URL" => $ob->base_url . $pathQ . "?action=ObjectProperties",
//						"EXTERNAL" => "NO",
//					),
					"IMAGE" => CheckStrCharsetForJson($fIcon),
				);
				if(strlen($fSize . $fDateCreate) > 0)
				{
					$arQQ["TAGS"] = CheckStrCharsetForJson($fSize ."  " . $fDateCreate);
				}

				$arResFiles[] = $arQQ;
				$filesNum++;
			}
		}
	}
	$res = array(
		"data" => $arResFiles,
		"TABLE_SETTINGS" => GetTableSettings($foldersNum, $filesNum),
	);

	$APPLICATION->RestartBuffer();
	echo json_encode($res);
	die();
}
elseif($action == "ObjectProperties")
{
	$arResult = array();

	$optionsQ = array (
		'path' => $ob->_path,
		'depth' => 0,
		'check_permissions' => true,
	);
	$arSelectedFieldsQ = array('NAME','FILE_TUPES', 'TIMESTAMP_X', 'FILE_SIZE');
	$filesQ = null;
	$arFilterQ = array();
	$resQ = $ob->PROPFIND($optionsQ, $filesQ, array("FILTER" => $arFilterQ, "COLUMNS" => $arSelectedFieldsQ, "return" => "nav_result", "get_clones" => "Y"));
	if(is_array($resQ) && array_key_exists("NAV_RESULT", $resQ))
	{
		if($arF = $resQ["NAV_RESULT"]->Fetch())
		{
			$arPropList = array("NAME" => 0, "DATE_CREATE" => 0, "FILE_SIZE" => 0);
			$arResult =  array_intersect_key($arF, $arPropList);
			$arResult["FILE_SIZE"] = 0;
			if(array_key_exists("PROPERTY_WEBDAV_SIZE_VALUE", $arF))
			{
				$arResult["FILE_SIZE"] = intval($arF["PROPERTY_WEBDAV_SIZE_VALUE"]);
			}
			$arResult["IMAGE"] = GetFilrIcon($compPath, $arF["PATH"], $arParams, $arF);
			$arResult["URL"] = $ob->base_url . CheckStrCharsetForJson($arF["PATH"]);
			$arResult["DESCRIPTION"] = $arF["PREVIEW_TEXT"];
			$arResult["DATE_MODIFIED"] = GetFileModificationDate($arF);
		}
	}

	if(count($arResult) <= 0)
	{
		die();
	}
	header('Content-Type: text/html; charset='.LANG_CHARSET);
	$this->IncludeComponentTemplate();
}
else
{
	$APPLICATION->RestartBuffer();
	$ob->base_GET();
	die;
}
?>