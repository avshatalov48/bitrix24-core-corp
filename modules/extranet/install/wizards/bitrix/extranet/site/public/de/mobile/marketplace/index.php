<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
Asset::getInstance()->addString(Bitrix\MobileApp\Mobile::getInstance()->getViewPort());
?>


<?$APPLICATION->IncludeComponent(
	"bitrix:app.layout",
	".default",
	array(
		"ID" => $_GET["id"],
		"COMPONENT_TEMPLATE" => ".default",
		"MOBILE"=>"Y",
		"LAZYLOAD" => isset($_GET["lazyload"]) && $_GET["lazyload"] === "Y" ? "Y" : "N",
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>