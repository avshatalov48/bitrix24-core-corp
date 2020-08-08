<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/timeman.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}
?> <?

if (IsModuleInstalled("timeman") && \Bitrix\Main\Loader::includeModule('timeman'))
{
	$APPLICATION->includeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		[
			"POPUP_COMPONENT_NAME" => "bitrix:timeman.worktime.stats",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => [
				'SCHEDULE_ID' => $_REQUEST['SCHEDULE_ID'],
			],
			"USE_UI_TOOLBAR" => "Y"
		]
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
	<div style="text-align: center;"><img src="images/<?=$lang?>/timeman.png"/></div>
	<p><?=GetMessage("TARIFF_RESTRICTION_TEXT2")?></p>
	<br/>
	<? if (\Bitrix\Main\Loader::includeModule('bitrix24')): ?>
	<div style="text-align: center;"><?CBitrix24::showTariffRestrictionButtons("timeman")?></div>
	<? endif; ?>
	<?
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>