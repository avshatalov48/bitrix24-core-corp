<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/index.php");

if (\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() === 'by')
{
	$APPLICATION->SetTitle(GetMessage("VI_PAGE_STAT_TITLE_BY"));
}
else
{
	$APPLICATION->SetTitle(GetMessage("VI_PAGE_STAT_TITLE"));
}
?>

<?$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:voximplant.start",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"USE_PADDING" => false,
		"USE_TOP_MENU" => true,
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
