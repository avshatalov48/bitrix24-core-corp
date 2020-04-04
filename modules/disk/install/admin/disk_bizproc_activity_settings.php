<?
define("MODULE_ID", "disk");
if($_REQUEST['entity']=="Bitrix\\Disk\\BizProcDocument")
{
	define("ENTITY", "Bitrix\\Disk\\BizProcDocument");
}
else
{
	define("ENTITY", "Bitrix\\Disk\\BizProcDocumentCompatible");
}
$fp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizprocdesigner/admin/bizproc_activity_settings.php";
if(file_exists($fp))
	require($fp);