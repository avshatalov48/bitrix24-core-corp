<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("mobileapp"))
	die();
$language = LANGUAGE_ID;
$os = CMobile::$platform;
$page = "index";
if(in_array($_REQUEST["page"], array("caldav", "cardav")))
	$page = $_REQUEST["page"];


$pagePath = $language."_".$page."_".$os;

$APPLICATION->SetPageProperty("BodyClass", "calendar-help-page");

$this->IncludeComponentTemplate($pagePath);
?>