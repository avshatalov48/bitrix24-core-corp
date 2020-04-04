<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/search/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:search.page", "icons", array(
	"RESTART" => "N",
	"CHECK_DATES" => "N",
	"USE_TITLE_RANK" => "N",
	"arrWHERE" => array(
		0 => "iblock_library",
	),
	"arrFILTER" => array(
		0 => "main",
		1 => "iblock_services",
		2 => "iblock_library",
		3 => "blog",
	),
	"arrFILTER_main" => array(
	),
	"arrFILTER_iblock_services" => array(
	),
	"arrFILTER_iblock_library" => array(
	),
	"SHOW_WHERE" => "Y",
	"PAGE_RESULT_COUNT" => "50",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "N",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"PAGER_TITLE" => GetMessage("PAGER_TITLE"),
	"PAGER_SHOW_ALWAYS" => "N",
	"PAGER_TEMPLATE" => "",
	"STRUCTURE_FILTER" => "structure",
	"AJAX_OPTION_ADDITIONAL" => ""
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>