<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_TEMPLATE_ID"))
	return;

//Attach template to  site
$obSite = CSite::GetList("def", "desc", Array("LID" => WIZARD_SITE_ID));
if ($arSite = $obSite->Fetch())
{
	$arTemplates = Array();
	$found = false;
	$foundEmpty = false;
	$allowGuests = "N";

	$obTemplate = CSite::GetTemplateList($arSite["LID"]);
	while($arTemplate = $obTemplate->Fetch())
	{
		if(!$found && trim($arTemplate["CONDITION"]) == '')
		{
			$arTemplate["TEMPLATE"] = WIZARD_TEMPLATE_ID;
			$found = true;
		}
		if($arTemplate["TEMPLATE"] == "login")
		{
			$foundEmpty = true;
			if($allowGuests == "Y")
				continue;
		}
		$arTemplates[]= $arTemplate;
	}

	if (!$found)
		$arTemplates[]= Array("CONDITION" => "", "SORT" => 150, "TEMPLATE" => WIZARD_TEMPLATE_ID);

	if (!$foundEmpty && $allowGuests <> "Y")
		$arTemplates[]= Array("CONDITION" => '((method_exists("CUser", "HasNoAccess") && $GLOBALS["USER"]->HasNoAccess()) || !$GLOBALS["USER"]->IsAuthorized()) && $_SERVER["REMOTE_USER"]==""', "SORT" => 250, "TEMPLATE" => "login");

	$arFields = Array(
		"TEMPLATE" => $arTemplates,
		"NAME" => $arSite["NAME"],
	);

	$obSite = new CSite();
	$obSite->Update($arSite["LID"], $arFields);
}
COption::SetOptionString("main", "wizard_template_id_extranet", WIZARD_TEMPLATE_ID);
?>
