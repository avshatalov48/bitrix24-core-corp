<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	die();

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
	&& $CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	die("Permission denied");
}

$i = new CCrmExternalSaleImport($_REQUEST["id"]);
$r = $i->SyncOrderData($_REQUEST["skip_bp"] == "Y");
if ($r != CCrmExternalSaleImport::SyncStatusError)
{
	echo CUtil::PhpToJSObject(array("result" => $r, "details" => $i->GetImportResult()->ToArray()));
}
else
{
	$str = '';
	foreach ($i->GetErrors() as $arError)
		$str .= sprintf("[%s] %s", $arError[0], htmlspecialchars($arError[1]))."<br>";
	echo CUtil::PhpToJSObject(array("result" => CCrmExternalSaleImport::SyncStatusError, "errors" => $str));
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>