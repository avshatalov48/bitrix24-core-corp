<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/settings/configs/event_log.php");
$APPLICATION->SetTitle(GetMessage("EVENT_LOG_TITLE"));

if (
	\Bitrix\Main\Loader::includeModule("bitrix24")
	&& (
		\CBitrix24::IsLicensePaid()
		|| \CBitrix24::IsNfrLicense()
		|| \CBitrix24::IsDemoLicense()
	)
	|| !IsModuleInstalled("bitrix24")
)
{
	$APPLICATION->IncludeComponent(
		"bitrix:event_list",
		"grid",
		Array(
			"COMPOSITE_FRAME_MODE" => "A",
			"COMPOSITE_FRAME_TYPE" => "AUTO",
			"FILTER" => array("USERS"),
			"PAGE_NUM" => "30",
			"USER_PATH" => "/company/personal/user/#user_id#/"
		)
	);
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>