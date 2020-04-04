<?
define('MODULE_ID', 'crm');
if (!empty($_REQUEST['entity']))
{
	if ($_REQUEST['entity'] === 'BitrixCrmIntegrationBizProcDocumentOrder')
	{
		define('ENTITY', 'Bitrix\Crm\Integration\BizProc\Document\Order');
	}
	else
	{
		define('ENTITY', $_REQUEST['entity']);
	}
}
else
{
	define('ENTITY', 'CCrmDocumentLead');
}

define('DISABLE_BIZPROC_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizprocdesigner/admin/bizproc_wf_settings.php');
?>