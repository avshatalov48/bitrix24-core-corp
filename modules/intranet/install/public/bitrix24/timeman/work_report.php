<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/work_report.php");

$APPLICATION->SetTitle(GetMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}
?>
<?
if (IsModuleInstalled("timeman"))
{
	$APPLICATION->IncludeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		array(
			"POPUP_COMPONENT_NAME" => "bitrix:timeman.report.weekly",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => array()
		)
	);
}
elseif (!(!IsModuleInstalled("timeman") && in_array($licenseType, array("company", "edu", "nfr"))))
{
	if (LANGUAGE_ID == "de" || LANGUAGE_ID == "la")
		$lang = LANGUAGE_ID;
	else
		$lang = LangSubst(LANGUAGE_ID);
	?>
	<p><?=GetMessage("TARIFF_RESTRICTION_TEXT")?></p>
	<div style="text-align: center;"><img src="images/<?=$lang?>/workreport.png"/></div>
	<p><?=GetMessage("TARIFF_RESTRICTION_TEXT2")?></p>
	<br/>
	<div style="text-align: center;"><?CBitrix24::showTariffRestrictionButtons("workreport")?></div>
	<?
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>