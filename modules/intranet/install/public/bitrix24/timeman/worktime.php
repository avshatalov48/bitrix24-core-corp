<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetPageProperty("BodyClass", "page-one-column");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intranet/public_bitrix24/timeman/timeman.php");
$APPLICATION->SetTitle(Loc::getMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}
?><?

if (ModuleManager::isModuleInstalled("timeman"))
{
	try
	{
		$APPLICATION->IncludeComponent("bitrix:timeman.worktime", "", [
			'ACTION' => $_REQUEST['MOD_ACTION'],
		]);
	}
	catch (\Bitrix\Main\AccessDeniedException $e)
	{
		echo $e->getMessage();
	}
}
elseif (!(!ModuleManager::isModuleInstalled("timeman") && in_array($licenseType, ["company", "edu", "nfr"])))
{
	if (LANGUAGE_ID == "de" || LANGUAGE_ID == "la")
	{
		$lang = LANGUAGE_ID;
	}
	else
	{
		$lang = LangSubst(LANGUAGE_ID);
	}
	?>
	<p><?= Loc::getMessage("TARIFF_RESTRICTION_TEXT") ?></p>
	<div style="text-align: center;"><img src="images/<?= $lang ?>/timeman.png"/></div>
	<p><?= Loc::getMessage("TARIFF_RESTRICTION_TEXT2") ?></p>
	<br/>
	<div style="text-align: center;"><?
		CBitrix24::showTariffRestrictionButtons("timeman") ?></div>
	<?
}
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>