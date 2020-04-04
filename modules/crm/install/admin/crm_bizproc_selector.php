<?
define('MODULE_ID', 'crm');
if (!empty($_REQUEST['entity']))
	define('ENTITY', $_REQUEST['entity']);
else
	define('ENTITY', 'CCrmDocumentLead');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizproc/admin/bizproc_selector.php');
?>