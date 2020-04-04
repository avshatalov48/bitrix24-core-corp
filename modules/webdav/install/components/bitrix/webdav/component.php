<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arError = array();
if ($arParams["SEF_MODE"] != "Y"):
	$arError[] = GetMessage("WD_ERROR_2");
endif;
$sapi = strtolower(php_sapi_name());
if (function_exists("apache_get_modules")):
	$res = apache_get_modules();
	if (!in_array("mod_rewrite", $res)):
		$arError[] = GetMessage("WD_ERROR_1");
	endif;
elseif ($sapi == "isapi"):
//	$arError[] = "";
endif;

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$componentPage = "index";
$arParams["RESOURCE_TYPE"] = ($arParams["RESOURCE_TYPE"] == "FOLDER" ? "FOLDER" : "IBLOCK");
if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
{
	//$arParams["IBLOCK_TYPE"]
	$arParams["IBLOCK_ID"] = intVal($arParams["IBLOCK_ID"]);
	$arParams['CHECK_CREATOR'] = "N"; // only for socnet
	$arParams['SHOW_TAGS'] = ($arParams['SHOW_TAGS'] == "Y" ? "Y" : "N");

	if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
	{
		$requestURL = $APPLICATION->GetCurPage();
		$arParams["SEF_FOLDER"] = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
		if($arParams["SEF_FOLDER"] != "/")
		{
			$arParams["SEF_FOLDER"] = "/" . Trim($arParams["SEF_FOLDER"], "/ \t\n\r\0\x0B") . "/";
		}
		$currentPageUrl = SubStr($requestURL, StrLen($arParams["SEF_FOLDER"]));

		$diskStorage = \Bitrix\Disk\Storage::load(array(
			'MODULE_ID' => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
			'ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(),
			'XML_ID' => (int)$arParams['IBLOCK_ID'],
		), array('ROOT_OBJECT'));

		if($diskStorage)
		{
			//it means /
			if(!$currentPageUrl || $currentPageUrl == 'index.php')
			{
				if($arParams["SEF_FOLDER"])
				{
					$arParams["SEF_FOLDER"] = rtrim($arParams["SEF_FOLDER"], '/');
				}
				LocalRedirect($arParams["SEF_FOLDER"] . '/path/');
			}

			$arParams['STORAGE'] = $diskStorage;

			$componentPage = '';
			$arVariablesD = array();

			$engine = new CComponentEngine($this);
			$engine->addGreedyPart("#PATH#");
			$engine->addGreedyPart("#FILE_PATH#");
			$engine->addGreedyPart("#TRASH_PATH#");
			$engine->addGreedyPart("#TRASH_FILE_PATH#");
			$engine->setResolveCallback(array(
				\Bitrix\Disk\Driver::getInstance()->getUrlManager(),
				"resolvePathComponentEngine"
			));

			$componentPage = $engine->guessComponentPath($arParams["SEF_FOLDER"], array(
				"trashcan_list" => "trashcan/#TRASH_PATH#",
				"trashcan_file_view" => "trash/file/#TRASH_FILE_PATH#",
				"folder_list" => "path/#PATH#",
				"folder_view" => "folder/#PATH#",
				"file_view" => "file/#FILE_PATH#",
				"file_history" => "file-history/#FILE_ID#",
				"file_old_view" => "element/view/#ELEMENT_ID#/",
				"external_link_list" => "external",
				"disk_help" => "help",

				"disk_bizproc_workflow_admin" => "bp/",
				"disk_bizproc_workflow_edit" => "bp_edit/#ID#/",
				"disk_start_bizproc" => "bp_start/#ELEMENT_ID#/",
				"disk_task" => "bp_task/#ID#/",
				"disk_task_list" => "bp_task_list/",
			), $arVariablesD);

			if ($componentPage === 'file_old_view' && !empty($arVariablesD['ELEMENT_ID']))
			{
				/** @var \Bitrix\Disk\File $diskFile */
				$diskFile = \Bitrix\Disk\File::load(array('XML_ID' => $arVariablesD['ELEMENT_ID']), array('STORAGE'));
				if($diskFile)
				{
					$needToRedirectPageUrl = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getPathFileDetail($diskFile);
				}

				$componentPage = null;
			}

			if(!empty($componentPage))
			{
				$APPLICATION->IncludeComponent("bitrix:disk.common", ".default", Array(
					'STORAGE' => $diskStorage,
					'SEF_MODE' => 'Y',
					'SEF_FOLDER' => $arParams['SEF_FOLDER'],
				), false, array("HIDE_ICONS" => "Y"));

				return;
			}
			elseif(empty($needToRedirectPageUrl))
			{
				if($arParams["SEF_FOLDER"])
				{
					$arParams["SEF_FOLDER"] = rtrim($arParams["SEF_FOLDER"], '/');
				}
				$needToRedirectPageUrl = $arParams["SEF_FOLDER"] . '/path/';
			}
		}
	}
}
else
{
	if (
		(strlen(trim($arParams["FOLDER"])) <= 0)
		|| (str_replace(array("///", "//"), "/", trim($arParams["FOLDER"])) == '/')
	)
	{
		ShowError(GetMessage("WD_FOLDER_EMPTY"));
		return 0;
	}
	$arParams["FOLDER"] = str_replace(array("\\\\", "\\", "///", "//"), "/", "/".trim($arParams["FOLDER"])."/");
	$arParams["FOLDER_PATH"] = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].$arParams["FOLDER"]);
	$arParams["USE_COMMENTS"] = "N";
}
$arParams['PERMISSION'] = false;
/***************** URL *********************************************/
$arParams['USE_WEBDAV'] = ($arParams["USE_WEBDAV"] == "N" && empty($arError) ? "N" : "Y");
$arParams['USE_AUTH'] = ($arParams['USE_AUTH'] == "Y" ? "Y" : "N");
$arParams['PUBLISH_TO_SOCNET'] = ($arParams['PUBLISH_TO_SOCNET'] == "N" ? "N" : "Y");
$arParams['AUTO_PUBLISH'] = ($arParams['AUTO_PUBLISH'] == "Y" ? "Y" : "N");
$arParams["NAME_FILE_PROPERTY"] = strToupper(trim(empty($arParams["NAME_FILE_PROPERTY"]) ? "FILE" : $arParams["NAME_FILE_PROPERTY"]));
$arParams['~BASE_URL'] = $arResult['BASE_URL'] = $arParams['~SEF_FOLDER'];
$arResult['BASE_URL'] = str_replace(":443", "", rtrim($arResult['BASE_URL'], '/'));
$arParams["BASE_URL"] = ($APPLICATION->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$arResult['BASE_URL']."/");
$arParams["REPLACE_SYMBOLS"] = ($arParams["REPLACE_SYMBOLS"] == "Y" ? "Y" : "N");
$arParams["CONVERT"] = ($arParams["AJAX_MODE"] == "Y" ? "full" : "default");
$arParams["FORM_ID"] = "webdavForm".$arParams["IBLOCK_ID"];
//$arParams["ELEMENT_ID"]
//$arParams["ACTION"]
//$arParams["USER_ID"]
/***************** TAGS ********************************************/
$arParams["TAGS_PAGE_ELEMENTS"] = intVal(empty($arParams["TAGS_PAGE_ELEMENTS"]) ? 50 : $arParams["TAGS_PAGE_ELEMENTS"]);
$arParams["TAGS_PERIOD"] = trim($arParams["TAGS_PERIOD"]);
$arParams["TAGS_INHERIT"] = ($arParams["TAGS_INHERIT"] == "N" ? "N" : "Y");
$arParams["TAGS_FONT_MAX"] = intVal(empty($arParams["TAGS_FONT_MAX"]) ? 50 : $arParams["TAGS_FONT_MAX"]);
$arParams["TAGS_FONT_MIN"] = intVal(empty($arParams["TAGS_FONT_MIN"]) ? 10 : $arParams["TAGS_FONT_MIN"]);
$arParams["TAGS_COLOR_NEW"] = (empty($arParams["TAGS_COLOR_NEW"]) ? "486DAA" : $arParams["TAGS_COLOR_NEW"]);
$arParams["TAGS_COLOR_OLD"] = (empty($arParams["TAGS_COLOR_OLD"]) ? "486DAA": $arParams["TAGS_COLOR_OLD"]);
$arParams["TAGS_SHOW_CHAIN"] = ($arParams["TAGS_SHOW_CHAIN"] == "N" ? "N" : "Y");
/***************** COMMENTS ****************************************/
$arParams["USE_COMMENTS"] = ($arParams["USE_COMMENTS"] == "Y" ? "Y" : "M");
/***************** Additional params *******************************/
$arParams["SHOW_WEBDAV"] = $arParams['USE_WEBDAV'];
$arParams["SHOW_NAVIGATION"] = ($arParams['SHOW_NAVIGATION'] == "Y" ? "Y" : "N");
$arParams["PREORDER"] = ($arParams['PREORDER'] == "N" ? "N" : "Y");
$arParams["DEFAULT_EDIT"] = ($arParams['DEFAULT_EDIT'] == "N" ? "N" : "Y");
$arParams["SHOW_NOTE"] = "";
if (!empty($arError) && $GLOBALS["USER"]->IsAdmin())
{
	$arParams["SHOW_NOTE"] = GetMessage("WD_ERROR_HEADER")."<ul><li>".implode("</li><li>", $arError)."</li></ul>";
}
/********************************************************************
				/Input params
********************************************************************/
$wdSefPathSettings = COption::GetOptionString('webdav', 'webdav_comp_sef_path_' . $arParams['IBLOCK_ID']);
if(!$wdSefPathSettings)
{
	COption::SetOptionString('webdav', 'webdav_comp_sef_path_' . $arParams['IBLOCK_ID'], serialize(array(
		'SEF_URL_TEMPLATES' => $arParams['SEF_URL_TEMPLATES'],
		'SEF_FOLDER' => $arParams['SEF_FOLDER'],
	)));
}

if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("WD_MODULE_IS_NOT_INSTALLED"));
	return 0;
elseif ($arParams["RESOURCE_TYPE"] == "IBLOCK"):
	if (!IsModuleInstalled("iblock")):
		ShowError(GetMessage("IB_MODULE_IS_NOT_INSTALLED"));
		return 0;
	elseif ($arParams["IBLOCK_ID"] <= 0):
		ShowError(GetMessage("WD_IBLOCK_ID_EMPTY"));
		return 0;
	endif;

	@define("MODULE_ID", "webdav");
	@define("ENTITY", "CIBlockDocumentWebdav");
	@define("DOCUMENT_TYPE", "iblock_".$arParams["IBLOCK_ID"]);
endif;

/********************************************************************
				Default values
********************************************************************/
$arResult["URL_TEMPLATES"] = array();
if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
{
	$arDefaultUrlTemplates404 = array(
		"sections" => "#PATH#",
		"sections_short" => "folder/view/#SECTION_ID#/#ELEMENT_ID#/#ELEMENT_NAME#",
		"section_edit" => "folder/edit/#SECTION_ID#/#ACTION#/",
		"section_view" => "folder/view/#SECTION_ID#/",

		"element_comment" => "element/comment/#TOPIC_ID#/#MESSAGE_ID#/",
		"element_edit" => "element/edit/#ACTION#/#ELEMENT_ID#/",
		//"element_file" => "f#ELEMENT_ID#/#ELEMENT_NAME#",
		"element_history" => "element/history/#ELEMENT_ID#/",
		"element_history_get" => "element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#",
		"element_version" => "element/version/#ACTION#/#ELEMENT_ID#/",
		"element_versions" => "element/versions/#ELEMENT_ID#/",
		"element_upload" => "element/upload/#SECTION_ID#/",

		"help" => "help",
		"user_view" => "/bitrix/admin/user_edit.php?ID=#USER_ID#&lang=".LANGUAGE_ID,
		"section" => "folder/view/#SECTION_ID#/",
		"element" => "element/view/#ELEMENT_ID#/",
		"search" => "search/",
		"connector" => "connector/",

		"webdav_bizproc_activity_settings" => "webdav_bizproc_activity_settings/",
		"webdav_bizproc_history" => "webdav_bizproc_history/#ELEMENT_ID#/",
		"webdav_bizproc_history_get" => "webdav_bizproc_history_get/#ELEMENT_ID#/#ID#/",
		"webdav_bizproc_log" => "webdav_bizproc_log/#ELEMENT_ID#/#ID#/",
		"webdav_bizproc_selector" => "webdav_bizproc_selector/",

		"webdav_bizproc_view" => "webdav_bizproc_view/#ELEMENT_ID#/",
		"webdav_bizproc_wf_settings" => "webdav_bizproc_wf_settings/",
		"webdav_bizproc_workflow_admin" => "webdav_bizproc_workflow_admin/",
		"webdav_bizproc_workflow_edit" => "webdav_bizproc_workflow_edit/#ID#/",
		"webdav_start_bizproc" => "webdav_start_bizproc/#ELEMENT_ID#/",
		"webdav_task_list" => "webdav_task_list/",
		"webdav_task" => "webdav_task/#ID#/");
}
else
{
	$arDefaultUrlTemplates404 = array(
		"sections" => "#PATH#",
		"section_edit" => "folder/#ACTION#/edit/#PATH#",

		"element_upload" => "element/upload/edit/#PATH#",
		"element_history_get" => "element/historyget/#PATH#",
		"element_edit" => "element/#ACTION#/edit/#PATH#",

		"help" => "help",
		"connector" => "connector/"
	);
}

$arDefaultVariableAliases404 = Array(
	"sections" => array("PAGE_NAME" => "PAGE_NAME", "PATH" => "PATH"),
	"sections" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ELEMENT_ID" => "ELEMENT_ID"),
	"section_edit" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ACTION" => "ACTION"),
	"section_view" => array("SECTION_ID" => "SECTION_ID"),

	"element" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ELEMENT_ID"=>"ELEMENT_ID"),

	"element_comment" => array("TOPIC_ID" => "TOPIC_ID", "MESSAGE_ID" => "MESSAGE_ID"),
	"element_edit" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ELEMENT_ID"=>"ELEMENT_ID", "ACTION" => "ACTION"),
	"element_upload" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID"),
	"element_history" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ELEMENT_ID"=>"ELEMENT_ID"),
	"element_history_get" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID", "ELEMENT_ID"=>"ELEMENT_ID"),

	"help" => array("PAGE_NAME" => "PAGE_NAME"),
	"connector" => array("PAGE_NAME" => "PAGE_NAME"),
	"user_view" => array("PAGE_NAME" => "PAGE_NAME", "USER_ID" => "USER_ID"),
	"section" => array("PAGE_NAME" => "PAGE_NAME", "SECTION_ID" => "SECTION_ID"),
	"search" => array("PAGE_NAME" => "PAGE_NAME"));

$arComponentVariables = Array(
	"SECTION_ID", "ELEMENT_ID", "PATH",
	"ACTION", "PAGE_NAME", "USER_ID");

$arDefaultVariableAliases = Array(
	"SECTION_ID" => "SECTION_ID", "ELEMENT_ID" => "ELEMENT_ID",
	"ACTION" => "ACTION", "PAGE_NAME" => "PAGE_NAME",
	"USER_ID" => "USER_ID", "PATH" => "PATH");

$requestURL = $sPath = $prevComponentPage = false;
if ($arParams["RESOURCE_TYPE"] == "FOLDER")
{
	$requestURL = $APPLICATION->GetCurPage(true);
	$arParams["SEF_FOLDER"] = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
	if ($arParams["SEF_FOLDER"] != "/")
		$arParams["SEF_FOLDER"] = "/".Trim($arParams["SEF_FOLDER"], "/ \t\n\r\0\x0B")."/";
	$currentPageUrl = SubStr($requestURL, StrLen($arParams["SEF_FOLDER"]));
	foreach ($arDefaultUrlTemplates404 as $url => $value)
	{
		$arResult["URL_TEMPLATES"][$url] = $arParams["SEF_FOLDER"]."/".$arDefaultUrlTemplates404[$url];
		$arParams["SEF_URL_TEMPLATES"][$url] = $arDefaultUrlTemplates404[$url];
		$currentPageTemplate = $arDefaultUrlTemplates404[$url];
		if (!$prevComponentPage && strpos($currentPageTemplate, "#PATH#") !== false && $currentPageTemplate != "#PATH#")
		{
			$pageTemplate = str_replace("#PATH#", "(.+)", $currentPageTemplate);
			$pageTemplateReg = preg_replace("'#[^#]+?#'", "([^/]+?)", $pageTemplate);
			if (preg_match("'^".$pageTemplateReg."$'", $currentPageUrl, $arValues))
			{
				$prevComponentPage = $url;
				$sPath = end($arValues);
				$requestURL = str_replace($sPath, "__empty_path_webdav__", $requestURL);
			}
		}
	}
}

$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);
$componentPage = CComponentEngine::ParseComponentPath(
	$arParams["SEF_FOLDER"],
	$arUrlTemplates,
	$arVariables,
	$requestURL);
if (empty($componentPage) && !empty($_REQUEST["PAGE_NAME"])):
	$componentPage = $_REQUEST["PAGE_NAME"];
endif;
$componentPage = (empty($componentPage) ? "sections" : $componentPage);
foreach ($arDefaultUrlTemplates404 as $url => $value)
{
	if (empty($arUrlTemplates[$url]))
		$arResult["URL_TEMPLATES"][$url] = $arParams["SEF_FOLDER"]."/".$arDefaultUrlTemplates404[$url];
	elseif (substr($arUrlTemplates[$url], 0, 1) == "/")
		$arResult["URL_TEMPLATES"][$url] = $arUrlTemplates[$url];
	else
		$arResult["URL_TEMPLATES"][$url] = $arParams["SEF_FOLDER"]."/".$arUrlTemplates[$url];

	$arResult["URL_TEMPLATES"][$url] = str_replace(array("///", "//"), "/", $arResult["URL_TEMPLATES"][$url]);
}

/********************************************************************
				/Default values
********************************************************************/
if ($arParams["RESOURCE_TYPE"] == "FOLDER")
{
	$arParams["OBJECT"] = $ob = new CWebDavFile($arParams, $arResult["BASE_URL"]);
}
else
{
	$arParams["OBJECT"] = $ob = new CWebDavIblock($arParams['IBLOCK_ID'], $arResult['BASE_URL'],
		$arParams + array("SHORT_PATH_TEMPLATE" => str_replace("//", "/", "/".$arUrlTemplates["sections_short"])));
}


if(!empty($needToRedirectPageUrl))
{
	if(!in_array($componentPage, array('element_history_get', 'sections'), true))
	{
		LocalRedirect($needToRedirectPageUrl);
	}
}

if (!empty($ob->arError))
{
	$e = new CAdminException($ob->arError);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	$res = $GLOBALS["APPLICATION"]->GetException();
	if ($res)
	{
		ShowError($res->GetString());
		return false;
	}
}
elseif ($ob->permission <= "D")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return false;
}


//=====
//if(class_exists("CWebDavExtLinks"))
//(
if(array_key_exists("GetExtLink", $_REQUEST) && intval($_REQUEST["GetExtLink"]) == 1)
{
	CWebDavExtLinks::CheckSessID();
	CWebDavExtLinks::CheckRights($ob);
	CUtil::JSPostUnescape();
	$o = array();
	$o["PASSWORD"] = (array_key_exists("PASSWORD", $_REQUEST) ? $_REQUEST["PASSWORD"] : "");	
	$o["LIFETIME_NUMBER"] = (array_key_exists("LIFETIME_NUMBER", $_REQUEST) ? intval($_REQUEST["LIFETIME_NUMBER"]) : 0);
	$o["LIFETIME_TYPE"] = (array_key_exists("LIFETIME_TYPE", $_REQUEST) ? $_REQUEST["LIFETIME_TYPE"] : "notlimited");
	$o["URL"] = CHTTP::urnDecode($ob->_path);
	$o["BASE_URL"] = $arResult['BASE_URL'];	
	$o["DESCRIPTION"] = (array_key_exists("DESCRIPTION", $_REQUEST) ? $_REQUEST["DESCRIPTION"] : "");
	$fileOptT = CWebDavExtLinks::GetFileOptions($ob);
	$o["F_SIZE"] = $fileOptT["F_SIZE"];
	CWebDavExtLinks::GetExtLink($arParams, $o);
}

if(!empty($_REQUEST['toWDController']) || !empty($_REQUEST['showInViewer']) || !empty($_REQUEST['editIn']) || !empty($_REQUEST['history']))
{
	include_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/webdav/tools/google/document_controller.php';
}

if(array_key_exists("GetDialogDiv", $_REQUEST) && intval($_REQUEST["GetDialogDiv"]) == 1)
{	
	CWebDavExtLinks::CheckSessID();
	CWebDavExtLinks::CheckRights($ob);
	CWebDavExtLinks::PrintDialogDiv($ob);
}

if(array_key_exists("DeleteLink", $_REQUEST) && strlen($_REQUEST["DeleteLink"]) > 0)
{
	CWebDavExtLinks::CheckSessID();
	CWebDavExtLinks::CheckRights($ob);
	CWebDavExtLinks::DeleteLink($_REQUEST["DeleteLink"]);
}

if(array_key_exists("DeleteAllLinks", $_REQUEST) && strlen($_REQUEST["DeleteAllLinks"]) > 0)
{
	CWebDavExtLinks::CheckSessID();
	CWebDavExtLinks::CheckRights($ob);
	CWebDavExtLinks::DeleteAllLinks($_REQUEST["DeleteAllLinks"], $ob);
}
//}
//=====


$ob->file_prop = $arParams["NAME_FILE_PROPERTY"];
$ob->replace_symbols = ($arParams["REPLACE_SYMBOLS"] == "Y" ? true : false);

$arParams['WORKFLOW'] = $ob->workflow;
$arResult['CURRENT_PATH'] = $ob->_path;
if (($ob->IsDavHeaders() || !in_array($_SERVER['REQUEST_METHOD'], array("GET", "POST"))) && $arParams["USE_AUTH"] == "Y" && !$USER->IsAuthorized()):
	$APPLICATION->RestartBuffer();
	CWebDavBase::SetAuthHeader();
	header('Content-length: 0');
	die();
endif;

//if (!$ob->CheckWebRights() && (!($arParams["USE_COMMENTS"] == "Y" && $_POST["save_product_review"] == "Y")))
if ((! $ob->e_rights) && !$ob->CheckWebRights("", array('action' => "read"), false) && (!($arParams["USE_COMMENTS"] == "Y" && $_POST["save_product_review"] == "Y")))
{
	$ob->SetStatus('403 Forbidden');
	$this->IncludeComponentTemplate('forbidden');
}
elseif (!$ob->IsMethodAllow($_SERVER['REQUEST_METHOD']))
{
	CHTTP::SetStatus('405 Method not allowed');
	header('Allow: ' . join(',', array_keys($ob->allow)));
	$this->IncludeComponentTemplate('notallowed');
}
elseif ($ob->IsDavHeaders() || !($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET'))
{
	$APPLICATION->RestartBuffer();
	$fn = 'base_' . $_SERVER['REQUEST_METHOD'];
	call_user_func(array(&$ob, $fn));
	die();
}
else
{
	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	if ($ob->workflow == 'bizproc' || $ob->workflow == 'bizproc_limited')
	{
		$arResult["URL_TEMPLATES"]["element_history"] = $arResult["URL_TEMPLATES"]["webdav_bizproc_history"];
		$arResult["URL_TEMPLATES"]["element_history_get"] = $arResult["URL_TEMPLATES"]["webdav_bizproc_history_get"];
	}

	if ($componentPage == "sections_short" || !in_array($componentPage, array_keys($arDefaultUrlTemplates404)))
		$componentPage = "sections";

	$arResult["~URL_TEMPLATES"] = $arUrlTemplates;
	$arResult["VARIABLES"] = $arVariables;
	$arResult["VARIABLES"]["PERMISSION"] = $ob->permission;
	if ( ($arParams["RESOURCE_TYPE"] == "IBLOCK") && ($ob->e_rights) )
		$arResult["VARIABLES"]["PERMISSION"] = $ob->GetPermission('IBLOCK', $arParams['IBLOCK_ID']);
	if ($arResult["VARIABLES"]["PATH"] == "__empty_path_webdav__" && $requestURL)
		$arResult["VARIABLES"]["PATH"] = ($sPath == "index.php" ? "" : $sPath);

	$arResult["ALIASES"] = $arVariableAliases;

	if ($componentPage == "element_comment") //"element_comment" => "element/comment/#TOPIC_ID#/#MESSAGE_ID#/",
	{
		$topicID = intval($arResult["VARIABLES"]['TOPIC_ID']);
		$messageID = intval($arResult["VARIABLES"]['MESSAGE_ID']);
		if (
			($topicID > 0) &&
			($messageID > 0) &&
			($arParams["USE_COMMENTS"] == "Y") &&
			CModule::IncludeModule('forum')
		)
		{
			$dbMessage = CForumMessage::GetList(array(), array(
				'FORUM_ID' => $arParams['FORUM_ID'],
				'TOPIC_ID' => $topicID,
				'NEW_TOPIC' => 'Y',
				'PARAM1' => 'IB'
			));
			if ($dbMessage && $arMessage = $dbMessage->Fetch())
			{
				$elementID = intval($arMessage['PARAM2']);
				if ($elementID > 0)
				{
					// check if this iblock
					$dbElement = CIBlockElement::GetList(array(), array('ID' => $elementID), false, false, array('IBLOCK_ID'));
					if (
						$dbElement
						&& ($arElement = $dbElement->Fetch())
						&& ($arElement['IBLOCK_ID'] == $arParams['IBLOCK_ID'])
					)
					{
						$elementUrl = str_replace('#ELEMENT_ID#', $elementID, $arResult['URL_TEMPLATES']['element']);
						$elementUrl .= (strpos($elementUrl, '?') !== false ? '&' : '?');
						$elementUrl .= $arParams["FORM_ID"].'_active_tab=tab_comments' ;

						LocalRedirect($elementUrl);
					}
				}
			}
		}
		LocalRedirect($arParams['SEF_FOLDER']);
	}
	elseif ($componentPage == "element_history_get" && $arParams["RESOURCE_TYPE"] == "FOLDER")
	{
		$APPLICATION->RestartBuffer();
		$ob->SetPath($arResult["VARIABLES"]["PATH"]);
		$options = array("path" => $arResult["VARIABLES"]["PATH"]);
		$ob->GET($options);
		$ob->SendFile($options, true);
		die();
	}
	elseif ($componentPage == "element_history_get")
	{
		if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
		{
			/** @var \Bitrix\Disk\File $diskFile */
			$diskFile = \Bitrix\Disk\File::load(array('XML_ID' => $arResult["VARIABLES"]['ID']), array('STORAGE'));
			if($diskFile && $diskFile->getStorage())
			{
				if($diskFile->canRead($diskFile->getStorage()->getCurrentUserSecurityContext()))
				{
					CFile::viewByUser($diskFile->getFile(), array("force_download" => true));
				}
			}
			LocalRedirect($needToRedirectPageUrl);
		}

		$APPLICATION->RestartBuffer();
		$ob->SendHistoryFile($arResult["VARIABLES"]["ELEMENT_ID"], 0, false, $_REQUEST);
		die();
	}
	elseif ($componentPage == "webdav_bizproc_history_get")
	{
		$APPLICATION->RestartBuffer();
		$ob->SendHistoryFile($arResult["VARIABLES"]["ELEMENT_ID"],
			($arResult["VARIABLES"]["ELEMENT_ID"] == $arResult["VARIABLES"]["ID"] ? 0 : $arResult["VARIABLES"]["ID"]));
		die();
	}
	elseif ($componentPage == "section")
	{
		$arResult["VARIABLES"]["PATH"] = "";
		$arResult["VARIABLES"]["SECTION_ID"] = intVal($arResult["VARIABLES"]["SECTION_ID"]);
		$componentPage = "sections";
	}
	elseif ($componentPage == "section_view" && intval($arResult["VARIABLES"]["SECTION_ID"]) > 0)
	{
		$secID = intval($arResult["VARIABLES"]["SECTION_ID"]);
		$secObj = $ob->GetObject(array('section_id' => $secID));
		if ($secObj['not_found'] == false)
		{
			$secPath = $ob->_get_path($secID);
			$secUrl = str_replace(array("///","//"), "/", CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["sections"], array("PATH" => $secPath)) );
			LocalRedirect($secUrl);
		}
		else
		{
			ShowError(GetMessage("WD_FOLDER_NOT_FOUND"));
			return false;
		}
	}
	elseif ($componentPage == "section_edit" && isset($_REQUEST["use_light_view"]) && strToUpper($_REQUEST["use_light_view"]) == "Y")
	{
		$componentPage = "section_edit_simple";
	}
	elseif ($componentPage == "element_upload" && isset($_REQUEST["use_light_view"]) && strToUpper($_REQUEST["use_light_view"]) == "Y")
	{
		$componentPage = "element_upload_simple";
	}
	elseif ($componentPage == "sections")
	{
		$arResult["VARIABLES"]["PATH"] = $ob->_path;
		if (!empty($ob->_path))
		{
			$ob->IsDir(array('check_permissions' => false));

			if ($ob->arParams["not_found"])
			{
				$ob->SetStatus('404 not found');
			}
			elseif (!empty($ob->arParams['file_name']))
			{
				if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
				{
					/** @var \Bitrix\Disk\File $diskFile */
					$diskFile = \Bitrix\Disk\File::load(array('XML_ID' => $ob->arParams['element_array']['ID']), array('STORAGE'));
					if($diskFile && $diskFile->getStorage())
					{
						if($diskFile->canRead($diskFile->getStorage()->getCurrentUserSecurityContext()))
						{
							CFile::viewByUser($diskFile->getFile(), array("force_download" => false));
						}
					}
				}

				$APPLICATION->RestartBuffer();
				$ob->base_GET();
				die();
			}
			if(!empty($needToRedirectPageUrl))
			{
				LocalRedirect($needToRedirectPageUrl);
			}

			$arResult["VARIABLES"]["SECTION_ID"] = $ob->arParams["item_id"];
		}
	}
	if ($componentPage == "sections" && strpos($_SERVER["REQUEST_URI"], "dialog=Y") !== false)
	{
		$componentPage = "sections_dialog";
	}
	if ($componentPage != "sections")
	{
		$ob->SetPath($arResult["VARIABLES"]["PATH"]);
	}
/********************************************************************
				Input params
********************************************************************/
/************** ADDITIONAL *****************************************/
	if ($componentPage == "sections" && empty($arParams["STR_TITLE"]))
	{
		// text from main
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$path = rtrim($GLOBALS["APPLICATION"]->GetCurDir());
		$chain_file_name = $DOC_ROOT.$path."/.section.php";
		if (file_exists($chain_file_name))
		{
			include($chain_file_name);
			if(strlen($sSectionName)>0)
				$arParams["STR_TITLE"] = $sSectionName;
		}
	}
/************** ADDITIONAL/*****************************************/
/********************************************************************
				/Input params
********************************************************************/
	if (!in_array($componentPage, array("sections_short", "sections_dialog", "section_edit_simple", "element_upload_simple")) &&
		!in_array($componentPage, array_keys($arDefaultUrlTemplates404)))
		$componentPage = "sections";
	if ($arParams["RESOURCE_TYPE"] == "FOLDER" && $componentPage == "element_view")
		$ob->SendHistoryFile($arResult["VARIABLES"]["ELEMENT_ID"]);
	elseif ($arParams["RESOURCE_TYPE"] == "FOLDER" && in_array(substr($componentPage, 0, 7), array("section", "element")))
		$componentPage = "disk_".$componentPage;

	$this->IncludeComponentTemplate($componentPage);
}
?>
