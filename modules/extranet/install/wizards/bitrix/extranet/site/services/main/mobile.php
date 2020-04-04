<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_SITE_ID"))
	return;

if (!defined("WIZARD_SITE_DIR"))
	return;
	
if (!IsModuleInstalled("mobile"))
{
	DeleteDirFilesEx(WIZARD_SITE_PATH."/mobile/");
	return;
}

if (WIZARD_IS_RERUN !== true)	
{
	$arAppTempalate = Array(
		"SORT" => 1,
		"CONDITION" => "CSite::InDir('".WIZARD_SITE_DIR."mobile/')",
		"TEMPLATE" => "mobile_app"
	);

	$arFields = Array("TEMPLATE" => array());
	$dbTemplates = CSite::GetTemplateList(WIZARD_SITE_ID);
	$mobileAppFound = false;
	while($template = $dbTemplates->Fetch())
	{
		if ($template["TEMPLATE"] == "mobile_app")
		{
			$mobileAppFound = true;
			$template = $arAppTempalate;
		}
		$arFields["TEMPLATE"][] = array(
			"TEMPLATE" => $template['TEMPLATE'],
			"SORT" => $template['SORT'],
			"CONDITION" => $template['CONDITION']
		);
	}
	if (!$mobileAppFound)
	{
		$arFields["TEMPLATE"][] = $arAppTempalate;
	}

	$obSite = new CSite;
	$arFields["LID"] = WIZARD_SITE_ID;
	$obSite->Update(WIZARD_SITE_ID, $arFields);

	CUrlRewriter::Add(
		array(
			"SITE_ID" => WIZARD_SITE_ID,
			"CONDITION" => "#^".WIZARD_SITE_DIR."mobile/webdav#",
			"ID" => "bitrix:mobile.webdav.file.list",
			"PATH" => WIZARD_SITE_DIR."mobile/webdav/index.php"
		)
	);
}
