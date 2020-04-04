<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_SITE_ID"))
	return;

if (!defined("WIZARD_SITE_DIR"))
	return;

if(!WIZARD_IS_INSTALLED)
{
	/*$rsSites = CSite::GetList($by="sort", $order="desc", array());
	if ($arSite = $rsSites->Fetch())
	{
		$FORMAT_DATE = $arSite["FORMAT_DATE"];
		$FORMAT_DATETIME = $arSite["FORMAT_DATETIME"];
		$EMAIL = $arSite["EMAIL"];		
		$LANGUAGE_ID = $arSite["LANGUAGE_ID"];
		$DOC_ROOT = $arSite["DOC_ROOT"];
		$CHARSET = $arSite["CHARSET"];
		$SERVER_NAME = $arSite["SERVER_NAME"];		
	}
	else
	{
		$FORMAT_DATE = "DD.MM.YYYY";
		$FORMAT_DATETIME = "DD.MM.YYYY HH:MI:SS";
		$EMAIL = COption::GetOptionString("main", "email_from");		
		$LANGUAGE_ID = LANGUAGE_ID;
		$DOC_ROOT = "";	
// wladart !!! charset !!!
		$CHARSET = (defined("BX_UTF") ? "UTF-8" : "windows-1251");
		$SERVER_NAME = $_SERVER["SERVER_NAME"];		
	}

	$arFields = array(
		"LID"				=> WIZARD_SITE_ID,
		"ACTIVE"			=> "Y",
		"SORT"				=> 100,
		"DEF"				=> "N",
		"NAME"				=> WIZARD_SITE_NAME,
		"DIR"				=> WIZARD_SITE_DIR,
		"FORMAT_DATE"		=> $FORMAT_DATE,
		"FORMAT_DATETIME"	=> $FORMAT_DATETIME,
		"CHARSET"			=> $CHARSET,
		"SITE_NAME"			=> WIZARD_SITE_NAME,
		"SERVER_NAME"		=> WIZARD_SITE_NAME,		
		"SERVER_NAME"		=> $SERVER_NAME,
		"EMAIL"				=> $EMAIL,
		"LANGUAGE_ID"		=> $LANGUAGE_ID,
		"DOC_ROOT"			=> $DOC_ROOT,
	);

	$obSite = new CSite;
	$result = $obSite->Add($arFields);
	if ($result)
	{*/
		COption::SetOptionString("main", "wizard_site_id", WIZARD_SITE_ID);
	/*	COption::SetOptionString("extranet", "extranet_site", WIZARD_SITE_ID);
	}*/
}
else
{
	COption::SetOptionString("main", "wizard_site_id", WIZARD_SITE_ID);
}
?>