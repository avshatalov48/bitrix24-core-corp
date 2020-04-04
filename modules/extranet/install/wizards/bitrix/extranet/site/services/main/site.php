<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

use Bitrix\Main\Localization\CultureTable;

//echo "WIZARD_SITE_ID=".WIZARD_SITE_ID." | ";
//echo "WIZARD_SITE_ID=".WIZARD_SITE_DIR." | ";
//echo "WIZARD_SITE_NAME=".WIZARD_SITE_NAME." | ";
//echo "WIZARD_SITE_PATH=".WIZARD_SITE_PATH." | ";
//echo "WIZARD_RELATIVE_PATH=".WIZARD_RELATIVE_PATH." | ";
//echo "WIZARD_ABSOLUTE_PATH=".WIZARD_ABSOLUTE_PATH." | ";
//echo "WIZARD_TEMPLATE_ID=".WIZARD_TEMPLATE_ID." | ";
//echo "WIZARD_TEMPLATE_RELATIVE_PATH=".WIZARD_TEMPLATE_RELATIVE_PATH." | ";
//echo "WIZARD_TEMPLATE_ABSOLUTE_PATH=".WIZARD_TEMPLATE_ABSOLUTE_PATH." | ";
//echo "WIZARD_THEME_ID=".WIZARD_THEME_ID." | ";
//echo "WIZARD_THEME_RELATIVE_PATH=".WIZARD_THEME_RELATIVE_PATH." | ";
//echo "WIZARD_THEME_ABSOLUTE_PATH=".WIZARD_THEME_ABSOLUTE_PATH." | ";
//echo "WIZARD_SERVICE_RELATIVE_PATH=".WIZARD_SERVICE_RELATIVE_PATH." | ";
//echo "WIZARD_SERVICE_ABSOLUTE_PATH=".WIZARD_SERVICE_ABSOLUTE_PATH." | ";
//echo "WIZARD_IS_RERUN=".WIZARD_IS_RERUN." | ";
//die();

if (!defined("WIZARD_SITE_ID"))
	return;

if (!defined("WIZARD_SITE_DIR"))
	return;

if (WIZARD_IS_RERUN !== true || WIZARD_B24_TO_CP)	
{
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
		$FORMAT_DATE = "DD.MM.YYYY";
		$FORMAT_DATETIME = "DD.MM.YYYY HH:MI:SS";
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
			"NAME" => WIZARD_SITE_ID,
			"CODE" => WIZARD_SITE_ID,
			"FORMAT_DATE" => $FORMAT_DATE,
			"FORMAT_DATETIME" => $FORMAT_DATETIME,
			"FORMAT_NAME" => $FORMAT_NAME,
			"CHARSET" => $CHARSET,
		));
		$cultureId = $addResult->getId();
	}

	$arFields = array(
		"LID" => WIZARD_SITE_ID,
		"ACTIVE" => "Y",
		"SORT" => 100,
		"DEF" => "N",
		"NAME" => WIZARD_SITE_NAME,
		"DIR" => WIZARD_SITE_DIR,
		"SITE_NAME" => WIZARD_SITE_NAME,
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
		COption::SetOptionString("main", "wizard_site_id", WIZARD_SITE_ID);
		COption::SetOptionString("extranet", "extranet_site", WIZARD_SITE_ID);
	}

	CExtranetWizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH."/", Array("SITE_DIR" => WIZARD_SITE_DIR));
	CExtranetWizardServices::ReplaceMacrosRecursive(WIZARD_TEMPLATE_ABSOLUTE_PATH."/", Array("SITE_DIR" => WIZARD_SITE_DIR));

	CUrlRewriter::Add(
		array(
			"SITE_ID" => WIZARD_SITE_ID,
			"CONDITION" => "#^".WIZARD_SITE_DIR."workgroups/#",
			"ID" => "bitrix:socialnetwork_group",
			"PATH" => WIZARD_SITE_DIR."workgroups/index.php"
		)
	);
	CUrlRewriter::Add(
		array(
			"SITE_ID" => WIZARD_SITE_ID,
			"CONDITION" => "#^".WIZARD_SITE_DIR."workgroups/create/#",
			"ID" => "bitrix:extranet.group_create",
			"PATH" => WIZARD_SITE_DIR."workgroups/create/index.php"
		)
	);
	CUrlRewriter::Add(
		array(
			"SITE_ID" => WIZARD_SITE_ID,
			"CONDITION" => "#^".WIZARD_SITE_DIR."contacts/personal/#",
			"ID" => "bitrix:socialnetwork_user",
			"PATH" => WIZARD_SITE_DIR."contacts/personal.php"
		)
	);	
}
else
{
	COption::SetOptionString("main", "wizard_site_id", WIZARD_SITE_ID);

	$siteName = COption::GetOptionString("main", "site_name", "", WIZARD_SITE_ID, true);
	if (strlen($siteName) > 0)
	{
		$arFields = Array(
			"NAME" => $siteName
		);

		$obSite = new CSite();
		$obSite->Update(WIZARD_SITE_ID, $arFields);	
	}
}
