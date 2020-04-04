<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_TEMPLATE_ID"))
	return;

if (in_array(WIZARD_TEMPLATE_ID, array("bright_extranet", "classic_extranet", "modern_extranet")))
{
	$templateDir = BX_PERSONAL_ROOT."/templates/".WIZARD_TEMPLATE_ID;

	CopyDirFiles(
		WIZARD_THEME_ABSOLUTE_PATH,
		$_SERVER["DOCUMENT_ROOT"].$templateDir,
		$rewrite = true, 
		$recursive = true,
		$delete_after_copy = false,
		$exclude = "description.php"
	);

	COption::SetOptionString("main", "wizard_site_logo_extranet", WIZARD_SITE_LOGO);
	COption::SetOptionString("main", "wizard_".WIZARD_TEMPLATE_ID."_theme_id_extranet", WIZARD_THEME_ID);

	$aOptions = CUserOptions::GetOption("main.interface", "global", array(), 0);
	$aOptions["theme_template"] = array(WIZARD_TEMPLATE_ID => WIZARD_THEME_ID);
	CUserOptions::SetOption("main.interface", "global", $aOptions, true);
}
?>