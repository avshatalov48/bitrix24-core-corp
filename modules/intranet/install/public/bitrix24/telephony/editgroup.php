<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/editgroup.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

$APPLICATION->SetTitle(GetMessage("VI_PAGE_EDIT_GROUP_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:voximplant.queue.edit",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"POPUP_COMPONENT_PARAMS" => array(
			'ID' => (int)$_REQUEST['ID'],
			'INLINE_MODE' => isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y'
		),
		"USE_PADDING" => false
	)
);?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
