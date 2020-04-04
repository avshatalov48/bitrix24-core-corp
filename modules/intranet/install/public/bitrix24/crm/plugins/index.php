<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
?>

<?$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'CMSPLUGINS',
		'ACTIVE_ITEM_ID' => 'CMSPLUGINS',
		'ENABLE_SEARCH' => false,
	)
);?>

<?$APPLICATION->IncludeComponent(
	'bitrix:crm.config.external_plugins',
	'',
	Array(
		'CMS_ID' => $_REQUEST['cms'],
	)
);?>

<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');