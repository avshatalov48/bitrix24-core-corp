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


//if ($arParams["INTERVAL"] <= 0)
	@set_time_limit(0);

$start_time = time();

if (function_exists("file_get_contents"))
	$DATA = file_get_contents("php://input");
elseif (isset($GLOBALS["HTTP_RAW_POST_DATA"]))
	$DATA = &$GLOBALS["HTTP_RAW_POST_DATA"];
else
	$DATA = false;

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
	$APPLICATION->RestartBuffer();
	Header("Pragma: no-cache");
}

ob_start();

if(!$USER->IsAuthorized())
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
	\Bitrix\Intranet\Internals\UserSubordinationTable::delayReInitialization();
	\Bitrix\Intranet\Internals\UserToDepartmentTable::delayReInitialization();

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/classes/general/hrxml.php");
	$first = microtime(1);
	$obMeta = new CUserHRXMLImport();
	try
	{
		libxml_disable_entity_loader(true);
		$xml = simplexml_load_string($DATA);
		if (!empty($xml))
		{
			$success = $obMeta->Init($arParams);
			if ($success)
				$obMeta->ImportData($xml);
		}
		else
		{
			$obMeta->errors[] = GetMessage('CC_BSC1_WRONG_OR_EMPTY');
		}
	}
	catch (Exception $e)
	{
		$obMeta->errors[] = $e->getMessage();
	}

	echo $obMeta->PrepareAnswer($xml->ApplicationArea);

	\Bitrix\Intranet\Internals\UserSubordinationTable::performReInitialization();
	\Bitrix\Intranet\Internals\UserToDepartmentTable::performReInitialization();
}

$contents = ob_get_contents();
ob_end_clean();

if(!$bDesignMode)
{
	if(toUpper(LANG_CHARSET) != "UTF-8")
		$contents = $APPLICATION->ConvertCharset($contents, LANG_CHARSET, "UTF-8");
	header("Content-Type: text/html; charset=UTF-8");
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
	<tr><td><?echo GetMessage("CP_BCI1_DEFAULT_EMAIL")?></td><td><?echo $arParams["DEFAULT_EMAIL"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_LOGIN_TEMPLATE")?></td><td><?echo $arParams["LOGIN_TEMPLATE"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_EMAIL_PROPERTY_XML_ID")?></td><td><?echo $arParams["EMAIL_PROPERTY_XML_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_LOGIN_PROPERTY_XML_ID")?></td><td><?echo $arParams["LOGIN_PROPERTY_XML_ID"]?></td></tr>
	<tr><td><?echo GetMessage("CP_BCI1_PASSWORD_PROPERTY_XML_ID")?></td><td><?echo $arParams["PASSWORD_PROPERTY_XML_ID"]?></td></tr>
	</table>
	<?
}
?>
