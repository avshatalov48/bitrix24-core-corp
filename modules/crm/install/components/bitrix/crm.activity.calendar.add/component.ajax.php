<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return false;
?>
<script>
BX.loadCSS('/bitrix/components/bitrix/crm.activity.calendar.add/templates/.default/style.css');
</script>
<?

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.calendar.add',
	'',
	array(
		'ENTITY_TYPE' => $_REQUEST['ENTITY_TYPE'],
		'ENTITY_ID' => $_REQUEST['ENTITY_ID'],
		'FORM_TYPE' => $_REQUEST['FORM_TYPE'],
		'RESULT_TAB' => (isset($_REQUEST['RESULT_TAB']) ? $_REQUEST['RESULT_TAB'] : ''),
	),
	false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>