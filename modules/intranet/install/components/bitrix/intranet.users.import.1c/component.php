<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*************************************************************************
	Processing of received parameters
*************************************************************************/

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);

$arParams["DEPARTMENTS_IBLOCK_ID"] = intval($arParams["DEPARTMENTS_IBLOCK_ID"]);
$arParams["ABSENCE_IBLOCK_ID"] = intval($arParams["ABSENCE_IBLOCK_ID"]);
$arParams["STATE_HISTORY_IBLOCK_ID"] = intval($arParams["STATE_HISTORY_IBLOCK_ID"]);

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
$arParams["INTERVAL"] = intval($arParams["INTERVAL"]);

if(!is_array($arParams["GROUP_PERMISSIONS"]))
	$arParams["GROUP_PERMISSIONS"] = array(1);

if(!is_array($arParams["SITE_LIST"]))
	$arParams["SITE_LIST"] = array();

$arParams["FILE_SIZE_LIMIT"] = intval($arParams["FILE_SIZE_LIMIT"]);
if($arParams["FILE_SIZE_LIMIT"] < 1)
	$arParams["FILE_SIZE_LIMIT"] = 200*1024; //200KB

$arParams["USE_ZIP"] = $arParams["USE_ZIP"]!="N";
$arParams["STRUCTURE_CHECK"] = $arParams["STRUCTURE_CHECK"] != "N";

if (!is_array($arParams['UPDATE_PROPERTIES']))
{
	$arParams['UPDATE_PROPERTIES'] = array('NAME','SECOND_NAME','LAST_NAME','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHOTO','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','WORK_POSITION','WORK_PHONE');
	$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
	if (!empty($arRes))
	{
		foreach ($arRes as $key => $val)
		{
			if ($val["EDIT_FORM_LABEL"] != "UF_STATE_FIRST" && $val["EDIT_FORM_LABEL"] != "UF_STATE_LAST" && $val["EDIT_FORM_LABEL"] != "UF_1C")
				$arParams['UPDATE_PROPERTIES'][] = $val["EDIT_FORM_LABEL"];
		}
	}
}

TrimArr($arParams['UPDATE_PROPERTIES']);
$arParams['UPDATE_PROPERTIES'][] = 'UF_STATE_FIRST';
$arParams['UPDATE_PROPERTIES'][] = 'UF_STATE_LAST';
$arParams["UPDATE_LOGIN"] = in_array('LOGIN', $arParams['UPDATE_PROPERTIES']);
$arParams["UPDATE_PASSWORD"] = in_array('PASSWORD', $arParams['UPDATE_PROPERTIES']);
$arParams["UPDATE_EMAIL"] = in_array('EMAIL', $arParams['UPDATE_PROPERTIES']);

$arParams['EMAIL_NOTIFY'] = $arParams['EMAIL_NOTIFY'] == 'Y' ? 'Y' : ($arParams['EMAIL_NOTIFY'] == 'E' ? 'E' : 'N');
$arParams['EMAIL_NOTIFY_IMMEDIATELY'] = $arParams['EMAIL_NOTIFY_IMMEDIATELY'] == 'Y' ? 'Y' : 'N';

//echo '<pre>'; print_r($arParams); echo '</pre>';
//die();

/*
$arParams["GENERATE_PREVIEW"] = $arParams["GENERATE_PREVIEW"]!="N";
if($arParams["GENERATE_PREVIEW"])
{
	$preview = array(
		intval($arParams["PREVIEW_WIDTH"]) > 1? intval($arParams["PREVIEW_WIDTH"]): 100,
		intval($arParams["PREVIEW_HEIGHT"]) > 1? intval($arParams["PREVIEW_HEIGHT"]): 100,
	);
}
else
{
	$preview = false;
}

$arParams["DETAIL_RESIZE"] = $arParams["DETAIL_RESIZE"]!="N";
if($arParams["DETAIL_RESIZE"])
{
	$detail = array(
		intval($arParams["DETAIL_WIDTH"]) > 1? intval($arParams["DETAIL_WIDTH"]): 300,
		intval($arParams["DETAIL_HEIGHT"]) > 1? intval($arParams["DETAIL_HEIGHT"]): 300,
	);
}
else
{
	$detail = false;
}
*/

if($arParams["INTERVAL"] <= 0)
	@set_time_limit(0);

$start_time = time();

$bUSER_HAVE_ACCESS = false;
if(isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]))
{
	$arUserGroupArray = $GLOBALS["USER"]->GetUserGroupArray();
	foreach($arParams["GROUP_PERMISSIONS"] as $PERM)
	{
		if(in_array($PERM, $arUserGroupArray))
		{
			$bUSER_HAVE_ACCESS = true;
			break;
		}
	}
}

$bDesignMode = $GLOBALS["APPLICATION"]->GetShowIncludeAreas() && is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->IsAdmin();
if(!$bDesignMode)
{
	if(!isset($_GET["mode"]))
		return;
	$APPLICATION->RestartBuffer();
	Header("Pragma: no-cache");
}

ob_start();

if($_GET["mode"] == "checkauth" && $USER->IsAuthorized())
{
	echo "success\n";
	echo session_name()."\n";
	echo session_id() ."\n";
}
elseif(!$USER->IsAuthorized())
{
	echo "failure\n",GetMessage("CC_BSC1_ERROR_AUTHORIZE");
}
elseif(!$bUSER_HAVE_ACCESS)
{
	echo "failure\n",GetMessage("CC_BSC1_PERMISSION_DENIED");
}
elseif(!CModule::IncludeModule('iblock'))
{
	echo "failure\n",GetMessage("CC_BSC1_ERROR_MODULE");
}
else
{
	//We have to strongly check all about file names at server side
	$DIR_NAME = "/".COption::GetOptionString("main", "upload_dir", "upload")."/1c_intranet";
	$ABS_FILE_NAME = false;
	$WORK_DIR_NAME = false;
	if(isset($_GET["filename"]) && (strlen($_GET["filename"])>0))
	{
		//This check for 1c server on linux
		$filename = preg_replace("#^(/tmp/|upload/1c/webdata)#", "", $_GET["filename"]);
		$filename = trim(str_replace("\\", "/", trim($filename)), "/");

		$io = CBXVirtualIo::GetInstance();
		$bBadFile = HasScriptExtension($filename)
			|| IsFileUnsafe($filename)
			|| !$io->ValidatePathString("/".$filename)
		;

		if(!$bBadFile)
		{
			$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"].$DIR_NAME, "/".$filename);
			if((strlen($FILE_NAME) > 1) && ($FILE_NAME === "/".$filename))
			{
				$ABS_FILE_NAME = $_SERVER["DOCUMENT_ROOT"].$DIR_NAME.$FILE_NAME;
				$WORK_DIR_NAME = substr($ABS_FILE_NAME, 0, strrpos($ABS_FILE_NAME, "/")+1);
			}
		}
	}

	\Bitrix\Intranet\Internals\UserSubordinationTable::delayReInitialization();
	\Bitrix\Intranet\Internals\UserToDepartmentTable::delayReInitialization();

	if(($_GET["mode"] == "file") && $ABS_FILE_NAME)
	{
		//Read http data
		if(function_exists("file_get_contents"))
			$DATA = file_get_contents("php://input");
		elseif(isset($GLOBALS["HTTP_RAW_POST_DATA"]))
			$DATA = &$GLOBALS["HTTP_RAW_POST_DATA"];
		else
			$DATA = false;
		//And save it the file
		if($DATA !== false)
		{
			CheckDirPath($ABS_FILE_NAME);
			if($fp = fopen($ABS_FILE_NAME, "ab"))
			{
				$result = fwrite($fp, $DATA);
				if($result === (function_exists("mb_strlen") ? mb_strlen($DATA, 'latin1'): strlen($DATA)))
				{
					echo "success";
					if($_SESSION["BX_CML2_IMPORT"]["zip"])
						$_SESSION["BX_CML2_IMPORT"]["zip"] = $ABS_FILE_NAME;
				}
				else
				{
					echo "failure\n",GetMessage("CC_BSC1_ERROR_FILE_WRITE", array("#FILE_NAME#"=>$FILE_NAME));
				}
			}
			else
			{
				echo "failure\n",GetMessage("CC_BSC1_ERROR_FILE_OPEN", array("#FILE_NAME#"=>$FILE_NAME));
			}
		}
		else
		{
			echo "failure\n",GetMessage("CC_BSC1_ERROR_HTTP_READ");
		}
	}
	elseif(($_GET["mode"] == "import") && $_SESSION["BX_CML2_IMPORT"]["zip"])
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/classes/".$GLOBALS["DBType"]."/cml2.php");

		if(!array_key_exists("last_zip_entry", $_SESSION["BX_CML2_IMPORT"]))
			$_SESSION["BX_CML2_IMPORT"]["last_zip_entry"] = "";

		$result = CIBlockXMLFile::UnZip($_SESSION["BX_CML2_IMPORT"]["zip"], $_SESSION["BX_CML2_IMPORT"]["last_zip_entry"]);
		if($result===false)
		{
			echo "failure\n",GetMessage("CC_BSC1_ZIP_ERROR");
		}
		elseif($result===true)
		{
			$_SESSION["BX_CML2_IMPORT"]["zip"] = false;
			echo "progress\n".GetMessage("CC_BSC1_ZIP_DONE");
		}
		else
		{
			$_SESSION["BX_CML2_IMPORT"]["last_zip_entry"] = $result;
			echo "progress\n".GetMessage("CC_BSC1_ZIP_PROGRESS");
		}
	}
	elseif(($_GET["mode"] == "import") && $ABS_FILE_NAME)
	{
		///////////////////////////////////// import part started  //////////////////////////////////////

		$NS = &$_SESSION["BX_CML2_IMPORT"]["NS"];
		$strError = "";
		$strMessage = "";

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/classes/".$GLOBALS["DBType"]."/cml2.php");
		if ($NS['STEP'] >= 4)
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/classes/general/cml2.php");

		if($NS["STEP"] < 1)
		{
			CIBlockXMLFile::DropTemporaryTables();
			$strMessage = GetMessage("CC_BSC1_TABLES_DROPPED");
			$NS["STEP"] = 1;
		}
		elseif($NS["STEP"] == 1)
		{
			if(CIBlockXMLFile::CreateTemporaryTables())
			{
				$strMessage = GetMessage("CC_BSC1_TABLES_CREATED");
				$NS["STEP"] = 2;
			}
			else
			{
				$strError = GetMessage("CC_BSC1_TABLE_CREATE_ERROR");
			}
		}
		elseif($NS["STEP"] == 2)
		{
			if($fp = fopen($ABS_FILE_NAME, "rb"))
			{
				$obXMLFile = new CIBlockXMLFile;
				if($obXMLFile->ReadXMLToDatabase($fp, $NS, $arParams["INTERVAL"]))
				{
					$NS["STEP"] = 3;
					$strMessage = GetMessage("CC_BSC1_FILE_READ");
				}
				else
				{
					$total = filesize($ABS_FILE_NAME);
					$strMessage = GetMessage("CC_BSC1_FILE_PROGRESS", array("#PERCENT#"=>$total > 0? round($obXMLFile->GetFilePosition()/$total*100, 2): 0));
				}
				fclose($fp);
			}
			else
			{
				$strError = GetMessage("CC_BSC1_FILE_ERROR");
			}
		}
		elseif($NS["STEP"] == 3)
		{
			if(CIBlockXMLFile::IndexTemporaryTables())
			{
				$strMessage = GetMessage("CC_BSC1_INDEX_CREATED");
				$NS["STEP"] = $arParams['STRUCTURE_CHECK'] ? 4 : 5;
			}
			else
				$strError = GetMessage("CC_BSC1_INDEX_CREATE_ERROR");
		}
		elseif($NS['STEP'] == 4)
		{
			$obCheck = new CUserCMLImport();
			$result = true;
			if ($obCheck->Init($NS, $WORK_DIR_NAME, $arParams))
			{
				$result = $obCheck->CheckStructure();
			}

			if ($result)
			{
				$NS['STEP']++;
				$strMessage = GetMessage("CC_BSC1_STRUCTURE_CHECKED");
			}
			else
			{
				if ($ex = $APPLICATION->GetException())
					$strError = $ex->GetString();
				else
					$strError = 'Some error occured while structure checking.';
			}
		}
		elseif($NS["STEP"] == 5)
		{
			//metadata loading: departments list into iblock and session

			$obMeta = new CUserCMLImport();
			$result = false;
			if ($obMeta->Init($NS, $WORK_DIR_NAME, $arParams))
			{
				$result = $obMeta->ImportMetaData();
			}

			if($result === true)
			{
				$strMessage = GetMessage("CC_BSC1_METADATA_IMPORTED");
				$NS["STEP"] = 6;
			}
			else
			{
				//$strError = GetMessage("CC_BSC1_METADATA_ERROR").implode("\n", $result);
				if ($ex = $APPLICATION->GetException())
					$strError = $ex->GetString();
				else
					$strError = 'Error occured while importing metadata';
			}
		}
		elseif($NS["STEP"] == 6)
		{
			define('INTR_SKIP_EVENT_ADD', 1);

			//print_r($NS);
			$bNotAll = $NS["DONE"]["ALL"] <= 0;
			$obImport = new CUserCMLImport();

			if ($obImport->Init($NS, $WORK_DIR_NAME, $arParams))
			{
				$result = $obImport->ImportUsers(false, $start_time, $arParams['INTERVAL']);
			}

			if($bNotAll && $NS["XML_ELEMENTS_PARENT"])
			{
				$rs = $DB->Query("select count(*) C from b_xml_tree where PARENT_ID = ".intval($NS["XML_ELEMENTS_PARENT"]));
				$ar = $rs->Fetch();
				$NS["DONE"]["ALL"] = $ar["C"];
			}

			$counter = 0;
			$current = 0;
			foreach($result as $key=>$value)
			{
				$NS["DONE"][$key] += $value;
				$counter += $value;
				$current += $NS["DONE"][$key];
			}

			if ($ex = $APPLICATION->GetException())
			{
				$strError = $ex->GetString();
			}
			else
			{
				if(!$counter)
				{
					$strMessage = GetMessage("CC_BSC1_DONE");
					$NS["STEP"] = 7;
					$NS['DONE'] = array();
					$NS['XML_LAST_ID'] = 0;
				}
				else
				{
					//echo 'TIME: '.(time()-$start_time)."\r\n";
					$strMessage = GetMessage("CC_BSC1_PROGRESS", array("#TOTAL#"=>$NS["DONE"]["ALL"],"#DONE#"=>$current));
				}
			}
		}
		elseif($NS["STEP"] == 7)
		{
//print_r($NS);
			$bNotAll = $NS["DONE"]["ALL"] <= 0;
			$obImport = new CUserCMLImport();

			if ($obImport->Init($NS, $WORK_DIR_NAME, $arParams))
			{
				$result = $obImport->ImportAbsence(false, $start_time, $arParams['INTERVAL']);
			}

			if($bNotAll && $NS["XML_ABSENCE_PARENT"])
			{
				$rs = $DB->Query("select count(*) C from b_xml_tree where PARENT_ID = ".intval($NS["XML_ABSENCE_PARENT"]));
				$ar = $rs->Fetch();
				$NS["DONE"]["ALL"] = $ar["C"];
			}

			$counter = 0;
			$current = 0;
			foreach($result as $key=>$value)
			{
				$NS["DONE"][$key] += $value;
				$counter += $value;
				$current += $NS["DONE"][$key];
			}

			if ($ex = $APPLICATION->GetException())
			{
				$strError = $ex->GetString();
			}
			else
			{
				if(!$counter)
				{
					$strMessage = GetMessage("CC_BSC2_DONE");
					$NS["STEP"] = 8;
				}
				else
				{
					//echo 'TIME: '.(time()-$start_time)."\r\n";
					$strMessage = GetMessage("CC_BSC2_PROGRESS", array("#TOTAL#"=>$NS["DONE"]["ALL"],"#DONE#"=>$current));
				}
			}
		}
		else
		{
			$NS["STEP"]++;
		}

		if($strError)
		{
			echo "failure\n";
			echo str_replace("<br>", "", $strError);
		}
		elseif($NS["STEP"] < 9)
		{
			echo "progress\n",$strMessage;
		}
		else
		{
			echo "success\n",GetMessage("CC_BSC1_IMPORT_SUCCESS");
			$_SESSION["BX_CML2_IMPORT"] = array(
				"zip" => $_SESSION["BX_CML2_IMPORT"]["zip"], //save from prev load
				"NS" => array(
					"STEP" => 0,
				),
				"SECTION_MAP" => false,
				"PRICES_MAP" => false,
			);
		}
	}
	elseif($_GET["mode"]=="init")
	{
		DeleteDirFilesEx($DIR_NAME);
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$DIR_NAME."/");
		if(!is_dir($_SERVER["DOCUMENT_ROOT"].$DIR_NAME))
		{
			echo "failure\n",GetMessage("CC_BSC1_ERROR_INIT");
		}
		else
		{
			$ht_name = $_SERVER["DOCUMENT_ROOT"].$DIR_NAME."/.htaccess";
			if(!file_exists($ht_name))
			{
				$fp = fopen($ht_name, "w");
				if($fp)
				{
					fwrite($fp, "Deny from All");
					fclose($fp);
					@chmod($ht_name, BX_FILE_PERMISSIONS);
				}
			}
			$_SESSION["BX_CML2_IMPORT"] = array(
				"zip" => $arParams["USE_ZIP"] && function_exists("zip_open"),
				"NS" => array(
					"STEP" => 0,
				),
				"SECTION_MAP" => false,
				"PRICES_MAP" => false,
			);
			echo "zip=".($_SESSION["BX_CML2_IMPORT"]["zip"]? "yes": "no")."\n";
			echo "file_limit=".$arParams["FILE_SIZE_LIMIT"]."\n";
		}
	}
	else
	{
		echo "failure\n",GetMessage("CC_BSC1_ERROR_UNKNOWN_COMMAND");
	}

	\Bitrix\Intranet\Internals\UserSubordinationTable::performReInitialization();
	\Bitrix\Intranet\Internals\UserToDepartmentTable::performReInitialization();
}

$contents = ob_get_contents();
ob_end_clean();

if(!$bDesignMode)
{
	if(toUpper(LANG_CHARSET) != "WINDOWS-1251")
		$contents = $APPLICATION->ConvertCharset($contents, LANG_CHARSET, "windows-1251");
	header("Content-Type: text/html; charset=windows-1251");

	echo $contents;
	die();
}
else
{
	$this->IncludeComponentLang(".parameters.php");

	?><table class="data-table">
	<tr><td><?echo GetMessage("CC_BCI1_IBLOCK_TYPE")?></td><td><?echo $arParams["IBLOCK_TYPE"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_DEPARTMENTS_IBLOCK_ID")?></td><td><?echo $arParams["DEPARTMENTS_IBLOCK_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_ABSENCE_IBLOCK_ID")?></td><td><?echo $arParams["ABSENCE_IBLOCK_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_STRUCTURE_CHECK")?></td><td><?echo $arParams["STRUCTURE_CHECK"] ? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")?></td></tr>
	<tr><td><?echo GetMessage("CC_BCI1_INTERVAL")?></td><td><?echo $arParams["INTERVAL"]?></td></tr>
	<tr><td><?echo GetMessage("CC_BCI1_FILE_SIZE_LIMIT")?></td><td><?echo $arParams["FILE_SIZE_LIMIT"]?></td></tr>
	<tr><td><?echo GetMessage("CC_BCI1_USE_ZIP")?></td><td><?echo $arParams["USE_ZIP"] ? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_UPDATE_PROPERTIES")?></td><td><pre><?=implode('<br />', $arParams["UPDATE_PROPERTIES"]);?></pre></td></tr>
	<?/*<tr><td><?echo GetMessage("CP_BCI1_UPDATE_PASSWORD")?></td><td><?echo $arParams["UPDATE_PASSWORD"] ? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_UPDATE_EMAIL")?></td><td><?echo $arParams["UPDATE_EMAIL"] ? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")?></td></tr>*/?>
	<tr><td><?echo GetMessage("CP_BCI1_DEFAULT_EMAIL")?></td><td><?echo $arParams["DEFAULT_EMAIL"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_LOGIN_TEMPLATE")?></td><td><?echo $arParams["LOGIN_TEMPLATE"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_EMAIL_PROPERTY_XML_ID")?></td><td><?echo $arParams["EMAIL_PROPERTY_XML_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_LOGIN_PROPERTY_XML_ID")?></td><td><?echo $arParams["LOGIN_PROPERTY_XML_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_PASSWORD_PROPERTY_XML_ID")?></td><td><?echo $arParams["PASSWORD_PROPERTY_XML_ID"]?></td></tr>
	<?/*<tr><td><?echo GetMessage("CC_BCI1_GENERATE_PREVIEW")?></td><td><?echo $arParams["GENERATE_PREVIEW"]? GetMessage("MAIN_YES")." ".$arParams["PREVIEW_WIDTH"]."x".$arParams["PREVIEW_HEIGHT"]: GetMessage("MAIN_NO")?></td></tr>
	<tr><td><?echo GetMessage("CC_BCI1_DETAIL_RESIZE")?></td><td><?echo $arParams["DETAIL_RESIZE"]? GetMessage("MAIN_YES")." ".$arParams["DETAIL_WIDTH"]."x".$arParams["DETAIL_HEIGHT"]: GetMessage("MAIN_NO")?></td></tr>*/?>
	</table>
	<?
}
?>
