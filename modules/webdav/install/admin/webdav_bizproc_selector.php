<?
define("MODULE_ID", "webdav");
if (isset($_REQUEST["entity"]) && ($_REQUEST["entity"] == "CIBlockDocumentWebdavSocnet")):
	define("ENTITY", "CIBlockDocumentWebdavSocnet");
else:
	define("ENTITY", "CIBlockDocumentWebdav");
endif;
$fp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/admin/bizproc_selector.php";
if(file_exists($fp))
{
	require($fp);
}
?>