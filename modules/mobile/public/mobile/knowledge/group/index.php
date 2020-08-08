<?php
define('SITE_TEMPLATE_ID', 'landing24');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>

<?$APPLICATION->IncludeComponent(
	'bitrix:landing.pub',
	'',
	array(
		'NOT_CHECK_DOMAIN' => 'Y',
		'SITE_TYPE' => 'GROUP',
		'DRAFT_MODE' => 'Y'
	),
	null,
	array(
		'HIDE_ICONS' => 'Y'
	)
);?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>