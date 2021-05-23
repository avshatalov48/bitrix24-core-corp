<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return false;

$APPLICATION->ShowCSS(true, true); // to upload CSS and ...
$APPLICATION->ShowHeadStrings(); // ... JS in popup
$APPLICATION->ShowHeadScripts();

$APPLICATION->IncludeComponent(
	'bitrix:crm.config.tax.rate.edit',
	'',
	array(
		'ID' => isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0,
		'TAX_ID' => isset($_REQUEST['TAX_ID']) ? intval($_REQUEST['TAX_ID']) : 0,
		'FORM_ID' => isset($_REQUEST['FORM_ID']) ? $_REQUEST['FORM_ID'] : ''
	),
	false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>
