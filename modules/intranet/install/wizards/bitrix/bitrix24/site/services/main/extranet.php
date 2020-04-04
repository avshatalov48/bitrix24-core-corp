<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!IsModuleInstalled("bitrix24"))
	return;

use Bitrix\Main\Localization\CultureTable;

/*$module_id = "extranet";
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/index.php");
$obModule = new $module_id;
if (!$obModule->IsInstalled()) $obModule->DoInstall();*/

$siteID = "ex";
$siteName = "Extranet";
$siteFolder = "/extranet/";

$rsSites = CSite::GetList($by="sort", $order="desc", array());
if ($arSite = $rsSites->Fetch())
{
	$FORMAT_DATE = $arSite["FORMAT_DATE"];
	$FORMAT_DATETIME = $arSite["FORMAT_DATETIME"];
	$FORMAT_NAME = (empty($arSite["FORMAT_NAME"])) ? CSite::GetDefaultNameFormat() : $arSite["FORMAT_NAME"];
	$EMAIL = $arSite["EMAIL"];
	$LANGUAGE_ID = $arSite["LANGUAGE_ID"];
	$DOC_ROOT = $arSite["DOC_ROOT"];
	$CHARSET = $arSite["CHARSET"];
	$SERVER_NAME = $arSite["SERVER_NAME"];
}
else
{
	$FORMAT_DATE = LANGUAGE_ID == "en" ? "MM/DD/YYYY" : "DD.MM.YYYY";
	$FORMAT_DATETIME = LANGUAGE_ID == "en" ? "MM/DD/YYYY H:MI:SS T" : "DD.MM.YYYY HH:MI:SS";
	$FORMAT_NAME = CSite::GetDefaultNameFormat();
	$EMAIL = COption::GetOptionString("main", "email_from");
	$LANGUAGE_ID = LANGUAGE_ID;
	$DOC_ROOT = "";
	$CHARSET = (defined("BX_UTF") ? "UTF-8" : "windows-1251");
	$SERVER_NAME = $_SERVER["SERVER_NAME"];
}

$culture = CultureTable::getRow(array('filter'=>array(
	"=FORMAT_DATE" => $FORMAT_DATE,
	"=FORMAT_DATETIME" => $FORMAT_DATETIME,
	"=FORMAT_NAME" => $FORMAT_NAME,
	"=CHARSET" => $CHARSET,
)));

if($culture)
{
	$cultureId = $culture["ID"];
}
else
{
	$addResult = CultureTable::add(array(
		"NAME" => $siteID,
		"CODE" => $siteID,
		"FORMAT_DATE" => $FORMAT_DATE,
		"FORMAT_DATETIME" => $FORMAT_DATETIME,
		"FORMAT_NAME" => $FORMAT_NAME,
		"CHARSET" => $CHARSET,
	));
	$cultureId = $addResult->getId();
}

$arFields = array(
	"LID" => $siteID,
	"ACTIVE" => "Y",
	"SORT" => 100,
	"DEF" => "N",
	"NAME" => $siteName,
	"DIR" => $siteFolder,
	"SITE_NAME" => $siteName,
	"SERVER_NAME" => $SERVER_NAME,
	"EMAIL" => $EMAIL,
	"LANGUAGE_ID" => $LANGUAGE_ID,
	"DOC_ROOT" => $DOC_ROOT,
	"CULTURE_ID" => $cultureId,
);

$obSite = new CSite;
$result = $obSite->Add($arFields);
if ($result)
{
	//COption::SetOptionString("main", "wizard_site_id", $siteID);
	COption::SetOptionString("extranet", "extranet_site", $siteID);
}
