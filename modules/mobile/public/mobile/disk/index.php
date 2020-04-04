<?require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
if(!empty($_GET['download']))
{
	define("EXTRANET_NO_REDIRECT", true);
}
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:mobile.disk.file.detail",
	".default",
	array(
	),
	false,
	array("HIDE_ICONS" => "Y")
);
?>
<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>