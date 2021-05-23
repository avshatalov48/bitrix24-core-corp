<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/detail.php");

$APPLICATION->SetTitle(GetMessage("VI_PAGE_STAT_DETAIL"));
?>

<?
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:voximplant.statistic.detail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			"COMPONENT_POPUP_TEMPLATE_NAME" => "",
			"COMPONENT_PARAMS" => 	array("LIMIT" => "30")
		]
	]
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
