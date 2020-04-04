<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_TEMPLATE_ID"))
	return;

//wizard customization file
$bxProductConfig = array();
if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php");

CopyDirFiles(
	$_SERVER['DOCUMENT_ROOT']."/bitrix/modules/intranet/install/templates/bitrix24/",
	WIZARD_SITE_ROOT_PATH."/bitrix/templates/bitrix24/",
	$rewrite = true,
	$recursive = true,
	$delete_after_copy = false
);

CopyDirFiles(
	$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/templates/login/",
	$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/login/",
	$rewrite = true,
	$recursive = true,
	$delete_after_copy = false
);

CopyDirFiles(
	$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/templates/pub/",
	$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/pub/",
	$rewrite = true,
	$recursive = true,
	$delete_after_copy = false
);

//Attach template to default site
$obSite = CSite::GetList($by = "def", $order = "desc", Array("LID" => WIZARD_SITE_ID));
if ($arSite = $obSite->Fetch())
{
	$arTemplates = Array();
	$found = false;
	$foundEmpty = false;
	$foundPub = false;
	$foundLanding = false;
	$obTemplate = CSite::GetTemplateList($arSite["LID"]);
	while($arTemplate = $obTemplate->Fetch())
	{
		if(!$found && strlen(trim($arTemplate["CONDITION"]))<=0)
		{
			$arTemplate["TEMPLATE"] = WIZARD_TEMPLATE_ID;
			$found = true;
		}
		if($arTemplate["TEMPLATE"] == "login")
		{
			$foundEmpty = true;
		}

		if (!$foundPub && trim($arTemplate["CONDITION"]) == "CSite::InDir('/pub/')")
		{
			$foundPub = true;
		}

		if (!$foundLanding && trim($arTemplate["CONDITION"]) == "preg_match('#/sites/site/[\\d]+/view/[\\d]+/#', \$GLOBALS['APPLICATION']->GetCurPage(0))")
		{
			$foundLanding = true;
		}

		$arTemplates[]= $arTemplate;
	}

	if (!$found)
	{
		$arTemplates[] = Array("CONDITION" => "", "SORT" => 150, "TEMPLATE" => WIZARD_TEMPLATE_ID);
		$arTemplates[] = array('CONDITION' => "CSite::InDir('/bitrix/tools/b24_emailrequest.php')", 'SORT' => 190, 'TEMPLATE' => 'login');
	}

	if (!$foundEmpty)
		$arTemplates[]= Array("CONDITION" => "!\$GLOBALS['USER']->IsAuthorized() && \$_SERVER['REMOTE_USER']==''", "SORT" => 250, "TEMPLATE" => "login");

	if (!$foundPub)
		$arTemplates[]= Array("CONDITION" => "CSite::InDir('/pub/')", "SORT" => 1, "TEMPLATE" => "pub");

	if (!$foundLanding)
		$arTemplates[]= Array("CONDITION" => "preg_match('#/sites/site/[\\d]+/view/[\\d]+/#', \$GLOBALS['APPLICATION']->GetCurPage(0)) && \$GLOBALS['USER']->IsAuthorized()", "SORT" => 300, "TEMPLATE" => "landing24");

	$arFields = Array(
		"TEMPLATE" => $arTemplates
	);

	$obSite = new CSite();
	$obSite->Update($arSite["LID"], $arFields);
}
COption::SetOptionString("main", "wizard_template_id", WIZARD_TEMPLATE_ID, false, WIZARD_SITE_ID);
CUserOptions::SetOption("main.interface", "global", array("theme"=> "lightgrey"), true);
?>