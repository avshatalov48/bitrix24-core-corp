<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/invite.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:intranet.invitation",
		"POPUP_COMPONENT_TEMPLATE_NAME" => ".default",
		"POPUP_COMPONENT_PARAMS" => [],
		"PAGE_MODE" => false
	),
	$component
);
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>