<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/classes/general/wizard_utils.php");

$arTemplates = array();
$templatesPath = CWizardUtil::GetRepositoryPath().CWizardUtil::MakeWizardPath("bitrix:extranet")."/site/templates";
$arTemplates = CExtranetWizardServices::GetTemplates($templatesPath);

$arSteps = array("WelcomeStep");
if (!empty($arTemplates))
{
	$arSteps[] = "SelectTemplateStep";
	$arSteps[] = "SelectThemeStep";
}
$arSteps[] = "SiteSettingsStep";
$arSteps[] = "DataInstallStep";
$arSteps[] = "FinishStep";

$arWizardDescription = Array(
	"NAME" => GetMessage("EXTRANET_WIZARD_NAME"), 
	"DESCRIPTION" => GetMessage("EXTRANET_WIZARD_DESC"), 
	"VERSION" => "1.0.0",
	"START_TYPE" => "WINDOW",
	"TEMPLATES" => Array(
		Array("SCRIPT" => "scripts/template.php", "CLASS" => "ExtranetWizardTemplate")
	),
/*
	"TEMPLATES" => Array(
		Array("SCRIPT" => "wizard_sol")
	),
*/
	"STEPS" => $arSteps
);
?>