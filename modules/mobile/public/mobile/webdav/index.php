<?require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:mobile.webdav.file.list",
	".default",
	Array(
		"NAME_FILE_PROPERTY" => "FILE",
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/mobile/webdav"
	),
	false
);
?>
<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>