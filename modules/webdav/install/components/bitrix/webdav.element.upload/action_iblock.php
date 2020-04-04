<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!($_REQUEST["save_upload"] == "Y" || ($_SERVER['REQUEST_METHOD'] == "POST" /*&& !empty($_FILES)*/))):
	return true;
endif;

$this->IncludeComponentLang("action_iblock.php");
if ($arParams["SECTION_ID"] <= 0 && $arParams["ROOT_SECTION_ID"] > 0)
	$arParams["SECTION_ID"] = $arParams["ROOT_SECTION_ID"];
$arError = array();
$arFile = array();
$result = array("FILE" => array(), "FILE_INFO" => array());
array_walk($_REQUEST, '__UnEscape');
array_walk($_FILES, '__UnEscape');
if (!empty($_FILES)):
	__CorrectFileName($_FILES);
endif;
class __WebdavUploader
{
	var $arParams;
	var $arResult;
	var $ob;
	function __construct($ob, $arResult, $arParams)
	{
		$this->ob = $ob;
		$this->arResult = $arResult;
		$this->arParams = $arParams;
	}
	function checkFile($arFile)
	{
		$ob = $this->ob;
		$arResult = $this->arResult;
		$arParams = $this->arParams;
		$arFileError = array();
		if (empty($arFile) || empty($arFile["name"]))
		{

		}
		elseif ($arFile["error"] > 0)
		{
			if ($arFile["error"] < 3)
			{
				$arFileError[] = array(
					"id" => "max_file_size",
					"text" => str_replace(
						array("#NAME#", "#SIZE#"),
						array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]/*intVal(ini_get('upload_max_filesize'))*/),
						GetMessage("WD_ERROR_UPLOAD_MAX_FILE_SIZE")));
			}
			elseif ($arFile["error"] == 3)
			{
				$arFileError[] = array(
					"id" => "bad_file",
					"text" => GetMessage("WD_ERROR_UPLOAD_BAD_FILE"));
			}
			else
			{
				$arFileError[] = array(
					"id" => "bad_file",
					"text" => GetMessage("WD_ERROR_UPLOAD_FILE_NOT_LOAD"));
			}
		}
		elseif (!$ob->CheckRights("PUT", true, $arFile["name"]))
		{
			$oError = $GLOBALS["APPLICATION"]->GetException();
			$arFileError[] = array(
				"id" => "bad_file",
				"text" => ($oError ? $oError->GetString()." [".$oError->GetId()."]" : GetMessage("WD_ERROR_UPLOAD_BAD_FILE")));
		}
		elseif ($arParams["UPLOAD_MAX_FILESIZE_BYTE"] > 0 && $arFile["size"] > $arParams["UPLOAD_MAX_FILESIZE_BYTE"])
		{
			$arFileError[] = array(
				"id" => "max_file_size",
				"text" => str_replace(array("#NAME#", "#SIZE#"), array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]),
				GetMessage("WD_ERROR_UPLOAD_MAX_FILE_SIZE")));
		}

		if (empty($arFileError))
			return true;
		else
			return $arFileError;
	}
	function saveFile(&$name, $arFile, &$arElement)
	{
		$ob = $this->ob;
		$arResult = $this->arResult;
		$arParams = $this->arParams;
		$res = array();
		$name = CWebDavIblock::CorrectName($name);
		$arDocumentStates = !isset($arParams['arDocumentStates'])? array() : $arParams['arDocumentStates'];

		$bRandom = isset($_REQUEST['random_folder']) && ($_REQUEST['random_folder'] == 'Y');
		$bDropped = ($bRandom || (isset($_REQUEST['dropped']) && ($_REQUEST['dropped'] == 'Y')));
		if($bDropped)
		{
			$arParams['SECTION_ID'] = $ob->GetMetaID("DROPPED");
			//Add (1), (2), etc. if name non unique in traget dir
			$mainPartName = $name;
			$newName = $mainPartName;
			$countNonUnique = 0;
			while(!$ob->CheckUniqueName($newName, $arParams["SECTION_ID"], $res))
			{
				$countNonUnique++;
				$newName = strstr($mainPartName, '.', true) . " ({$countNonUnique})" . strstr($mainPartName, '.');
			}
			$name = $newName;
		}
		elseif (!$ob->CheckUniqueName($name, $arParams["SECTION_ID"], $res))
		{
			if ($res["object"] == "section")
			{
				$arFileError[] = array(
					"id" => "double_name_section",
					"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_SECTION")));
			}
			else
			{
				if ($res["data"]["ID"] == $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"])
					$arElement = $arResult["ELEMENT"];
				else
					$arElement = $res["data"];

				if ($ob->workflow == "bizproc")
				{
					$docID = $arParams['DOCUMENT_TYPE'];
					$docID[2] = $res["data"]["ID"];
					$arDocumentStates = CBPDocument::GetDocumentStates( $arParams["DOCUMENT_TYPE"], $docID);
				}

				if ($arParams["USE_WORKFLOW"] == "Y" && intVal($res["data"]["WF_PARENT_ELEMENT_ID"]) > 0)
					$arElement["ID"] = $res["data"]["WF_PARENT_ELEMENT_ID"];
				if ($_REQUEST["overview"] != "Y")
				{
					if ($name != $arFile["name"])
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace(
								array("#NAME#", "#TITLE#"),
								array($arFile["name"], $name), GetMessage("WD_ERROR_DOUBLE_NAME_TITLE")));
					else
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT")));
				}
				elseif ($arParams["CHECK_CREATOR"] == "Y" && $arElement["CREATED_BY"] != $GLOBALS['USER']->GetId())
				{
					$arFileError[] = array(
						"id" => "double_name_element",
						"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT_NOT_REWRITE")));
				}
				elseif ($arParams["USE_WORKFLOW"] == "Y" &&
					($res["data"]["WF_STATUS_ID"] > 1 && $arResult["WF_STATUSES_PERMISSION"][$res["data"]["WF_STATUS_ID"]] < 2))
				{
					if ($name != $arFile["name"])
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace(
								array("#NAME#", "#TITLE#"),
								array($arFile["name"], $name), GetMessage("WD_ERROR_DOUBLE_NAME_TITLE")));
					else
						$arFileError[] = array(
							"id" => "double_name_element_and_bad_permission",
							"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT")));
				}
			}
		}
		if (($arParams['ELEMENT_ID']>0) && ($arResult['ELEMENT']['FILE_EXTENTION'] != strtolower(strrchr($arFile["name"], '.'))))
		{
			$arFileError[] = array(
				"id" => "extensions_dont_match",
				"text" => GetMessage("WD_ERROR_EXTENSIONS_DONT_MATCH"));
		}

		//this is new element
		if(empty($arElement))
		{
			if(!$ob->CheckWebRights("PUT", array(
				'arElement' => array(
					'is_dir' => false,
					'parent_id' => $arParams["SECTION_ID"]
				),
				'action' => 'create',
				'create_element_in_section' => true
			), false))
			{
				$arFileError[] = array(
					"id" => "bad_file",
					"text" => ($ob->LAST_ERROR ? $ob->LAST_ERROR : GetMessage("WD_ERROR_UPLOAD_BAD_FILE")));
			}
		}

		if (empty($arFileError))
		{
			$options = array(
				"new" => empty($arElement),
				'dropped' => $bDropped,
				"arFile" => $arFile,
				"arDocumentStates" => $arDocumentStates,
				"arUserGroups" => $ob->USER["GROUPS"],
				"FILE_NAME" => $name,
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"IBLOCK_SECTION_ID" => $arParams["SECTION_ID"],
				"TAGS" => $arFile["TAGS"],
				"PREVIEW_TEXT" => $arFile["DESCRIPTION"]);
			if (intVal($_POST["WF_STATUS_ID"]) > 0)
				$options["WF_STATUS_ID"] = $_POST["WF_STATUS_ID"];

			if (!empty($arElement))
				$options["ELEMENT_ID"] = $arElement["ID"];
			else
				$options["arUserGroups"][] = "Author";

			$options['USER_FIELDS'] = array();
			$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields($ob->GetUfEntity(), $options['USER_FIELDS']);

			if (!$ob->put_commit($options))
			{
				$arFileError[] = array(
					"id" => "error_put",
					"text" => $ob->LAST_ERROR,
				);
			}
			else
			{
				$arElement['ID'] = $options['ELEMENT_ID'];
			}
			$arElement["dropped"] = ($bDropped ? "Y" : "N");
		}
		if (empty($arFileError))
		{
			$io = CBXVirtualIo::GetInstance();
			$file = $io->GetFile($arFile["tmp_name"]);
			$file->unlink();
			return true;
		}
		return $arFileError;
	}
	function handleFile($hash, &$file, &$package, &$upload, &$error)
	{
		$ob = $this->ob;
		$arResult = $this->arResult;
		$arParams = $this->arParams;
		$name = $file["name"];
		$arFile = $file["files"]["default"];
		$arElement = array();
		if (($arFileError = $this->saveFile($name, $arFile, $arElement)) && $arFileError !== true)
		{
			$error = new CAdminException($arFileError);
			$error = $error->GetString();
			return false;
		}
		$file["element_id"] = $arElement["ID"];
		$file["url_get"] = WDAddPageParams($this->getUrl(), array('result' => 'doc'.$arElement["ID"]));
		$file["url_show"] = str_replace("#ELEMENT_ID#", $arElement["ID"], $arResult["URL"]["EDIT"]);
		return true;
	}
	function getUrl()
	{
		$ob = $this->ob;
		$arResult = $this->arResult;
		$arParams = $this->arParams;
		global $APPLICATION;
		$url = ($APPLICATION->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']);
		if ($arParams['ELEMENT_ID'] > 0) {
			$url .= $arResult["URL"]["VIEW"] . (strpos($url, '?') === false ? '?':'&') . "result=uploaded";
		} else {
			$url .= (!empty($_REQUEST["wd_upload_apply"]) ? $arResult["URL"]["~UPLOAD"] : $arResult["URL"]["~SECTIONS"]);
		}
		return $url;
	}
}
if(isset($arDocumentStates))
{
	$arParams['arDocumentStates'] = $arDocumentStates;
}
$wuo = new __WebdavUploader($ob, $arResult, $arParams);
$arParams["bxu"] = new CFileUploader(
	array(
		"allowUpload" => "A",
		"uploadMaxFilesize" => $arParams["UPLOAD_MAX_FILESIZE_BYTE"],
		"events" => array("onFileIsUploaded" => array($wuo, "handleFile"))),
	"get");

if ($_SERVER['REQUEST_METHOD'] == "POST" && empty($_POST))
{
	$arError["bad_post"] = array(
		"id" => "bad_post",
		"text" => str_replace(
			"#SIZE#", 
			$arParams["UPLOAD_MAX_FILESIZE"]/*intVal(ini_get('post_max_size'))*/, 
			GetMessage("WD_ERROR_BAD_POST")));
	// format answer
	$view_mode = ($_REQUEST["view_mode"] != "form" ? "applet" : "form");
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".strToLower($GLOBALS["DB"]->type)."/favorites.php");
		$arUserSettings = CUserOptions::GetOption("webdav", "upload_settings", '');
		if (CheckSerializedData($arUserSettings))
			$arUserSettings = @unserialize($arUserSettings);
		$arUserSettings = (is_array($arUserSettings) ? $arUserSettings : array());
		$view_mode = $arUserSettings["view_mode"];
	}
	$_REQUEST["AJAX_CALL"] = ($view_mode != "form" ? "Y" : "N");
	if (strpos(strToLower($_SERVER['HTTP_USER_AGENT']), "opera") !== false)
		$_REQUEST["AJAX_CALL"] = "N";
	$_REQUEST["CONVERT"] = $_REQUEST["AJAX_CALL"];
}
elseif (!check_bitrix_sessid())
{
	$arError["bad_sessid"] = array(
		"id" => "bad_sessid",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif (empty($_FILES))
{
	$arError["empty_files"] = array(
		"id" => "empty_files",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif ($arParams["USE_WORKFLOW"] == "Y" && empty($arResult["WF_STATUSES"][$_REQUEST["WF_STATUS_ID"]]))
{
	$arError["bad_status"] = array(
		"id" => "bad_status",
		"text" => GetMessage("WD_ERROR_BAD_STATUS"));
}
elseif (!(is_object($arParams["bxu"]) && $arParams["bxu"]->checkPost()))
{
/************** Create file array ***********************************/
	$i = 1;
	$number = intVal($_REQUEST["PackageIndex"]) * intVal(!empty($_REQUEST["FilesPerOnePackageCount"]) ?
		$_REQUEST["FilesPerOnePackageCount"] : $arParams["UPLOAD_MAX_FILE"]) + $i;
	$arFile = $_FILES["SourceFile_".$i] + array("TAGS" => $_REQUEST["Tag_".$number], "DESCRIPTION" => $_REQUEST["Description_".$number]);
	$arElement = array();
	$name = $arFile["name"];

	if (!empty($_REQUEST["Title_".$number]))
	{
		$name = $_REQUEST["Title_".$number];
		if (!(strPos($name, ".") > 0))
		{
			$f = pathinfo($arFile["name"]);
			$name = $_REQUEST["Title_".$number].".".$f["extension"];
		}
	}

	if ((($arFileError = $wuo->checkFile($arFile)) && $arFileError === true) &&
		(($arFileError = $wuo->saveFile($name, $arFile, $arElement)) && $arFileError === true))
	{
		if (isset($_REQUEST["UploadUnlock"]))
			$ob->UNLOCK($options = array("element_id" => intval($arParams["ELEMENT_ID"])));
		$arFields["ID"] = $arElement["ID"];
		if(function_exists('BXIBlockAfterSave'))
			BXIBlockAfterSave($arFields);
		$file_res = array("status" => "success");
		if (!empty($ob->LAST_ERROR))
			$file_res = array("status" => "error", "error" => array(array("id" => "error", "text" => $ob->LAST_ERROR)));
	}
	else
	{
		$bVarsFromForm = true;
		$file_res = array("status" => "error", "error" => $arFileError);
	}
	// Main info about file
	// Additional info about file
	$file_res["id"] = $arElement["ID"];
	$file_res["number"] = $number;
	$file_res["title"] = $name;
	$file_res["description"] = $arFields["PREVIEW_TEXT"];
	$file_res["content_type"] = $arFile["type"];
	if (CFile::IsImage($name, $arFile["type"]))
	{
		$file_res["width"] = 0;
		$file_res["height"] = 0;
		$imgArray = CFile::GetImageSize($arFile["tmp_name"], true);
		if(is_array($imgArray))
		{
			$file_res["width"] = $imgArray[0];
			$file_res["height"] = $imgArray[1];
		}
	}
	$result["FILE"][$name] = $file_res;
	$result["FILE_INFO"][$arFile["name"]] = $file_res;
}
/************** Answer **********************************************/
$url = $wuo->getUrl();
$bVarsFromForm = ($bVarsFromForm ? $bVarsFromForm : !empty($arError));
$uploader = array();
if (($_REQUEST["AJAX_CALL"] == "Y") && ($_REQUEST['SIMPLE_UPLOAD'] != "Y")) 
{
	$cache_id = "image_uploader_".preg_replace("/[^a-z0-9]+/is", "_", $_REQUEST["PackageGuid"]);
	$cache_path = "/bitrix/webdav/image_uploader/";
	if ($cache->InitCache(3600, $cache_id, $cache_path))
	{
		$res = $cache->GetVars();
		if (is_array($res["uploader"]))
			$uploader = $res["uploader"];
	}
}

if (empty($uploader))
	$uploader = array("fatal_errors" => array(), "files" => array(), "section_id" => $arParams["SECTION_ID"]);

$uploader["fatal_errors"] = array_merge($uploader["fatal_errors"], $arError);
$uploader["files"] = array_merge($uploader["files"], $result["FILE"]);
$uploader["storage"] = 'webdav';

if ($_REQUEST["AJAX_CALL"] == "Y")
{
	$cache->Clean($cache_id, $cache_path);
	$cache->StartDataCache(3600, $cache_id, $cache_path);
	$cache->EndDataCache(array("uploader"=>$uploader));
}
if (empty($_REQUEST["wd_upload_apply"]))
{
	$uploader["url"] = $url;
	if (!empty($file_res['id']) && $arParams['ELEMENT_ID']==0)
	{
		$uploader["url"] = WDAddPageParams($uploader["url"], array('result' => 'doc'.$file_res['id']));
		$uploader["element_id"] = $file_res["id"];
	}
}
$arResult["RETURN_DATA"] = $uploader;

if ($_REQUEST["FORMAT_ANSWER"] != "return")
{
	if ($_REQUEST["AJAX_CALL"] == "Y")
	{
		$APPLICATION->RestartBuffer();
		//if ($_REQUEST["CONVERT"] == "Y")
			//array_walk($uploader, '__Escape');
		?><?=CUtil::PhpToJSObject($uploader);?><?
		die();
	}
	elseif (!$bVarsFromForm)
	{
		LocalRedirect($url);
	}
}
else 
{
	$arResult["RETURN_DATA"]["current_files"] = $result["FILE_INFO"];
	$arResult["RETURN_DATA"]["url"] = $url;
	if ($_REQUEST["AJAX_CALL"] == "Y" || !$bVarsFromForm)
	{
		return $arResult["RETURN_DATA"];
	}
}

if ($bVarsFromForm)
{
	if (!empty($uploader['fatal_errors']))
		$arResult["ERROR_MESSAGE"] = WDShowError($uploader['fatal_errors']);
	else 
	{
		foreach ($uploader['files'] as $res)
			$arResult["ERROR_MESSAGE"] .= WDShowError($res["error"])."<br />";
	}
}
?>
