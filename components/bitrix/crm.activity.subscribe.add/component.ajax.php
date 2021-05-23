<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return false;
?>
<script>
BX.loadCSS('/bitrix/components/bitrix/crm.activity.subscribe.add/templates/.default/style.css');
</script>
<?

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.subscribe.add',
	'',
	array(
		'ENTITY_TYPE' => isset($_REQUEST['ENTITY_TYPE']) ? $_REQUEST['ENTITY_TYPE'] : null,
		'ENTITY_ID' => isset($_REQUEST['ENTITY_ID']) ? $_REQUEST['ENTITY_ID'] : null,
		'FORM_TYPE' => isset($_REQUEST['FORM_TYPE']) ? $_REQUEST['FORM_TYPE'] : null,
		'FORM_ENTITY_TYPE' =>  isset($_REQUEST['FORM_ENTITY_TYPE']) ? $_REQUEST['FORM_ENTITY_TYPE'] : null,
		'FORM_ENTITY_ID' =>  isset($_REQUEST['FORM_ENTITY_ID']) ? $_REQUEST['FORM_ENTITY_ID'] : null
	),
	false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>