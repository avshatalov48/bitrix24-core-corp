<?
define("MODULE_ID", "webdav");
if (isset($_REQUEST["entity"]) && ($_REQUEST["entity"] == "CIBlockDocumentWebdavSocnet")):
	define("ENTITY", "CIBlockDocumentWebdavSocnet");
else:
	define("ENTITY", "CIBlockDocumentWebdav");
endif;
$fp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizprocdesigner/admin/bizproc_activity_settings.php";
if(file_exists($fp))
{
	require($fp);
}
?>