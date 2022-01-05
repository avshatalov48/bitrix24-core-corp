<?php
define('SITE_TEMPLATE_ID', 'landing24');
define('LANDING_PUB_INTRANET_MODE', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?>

<?$APPLICATION->IncludeComponent(
	'bitrix:landing.pub',
	'',
	array(
		'NOT_CHECK_DOMAIN' => 'Y',
		'SITE_TYPE' => 'KNOWLEDGE',
		'DRAFT_MODE' => 'Y',
		'CHECK_PERMISSIONS' => 'Y'
	),
	null,
	array(
		'HIDE_ICONS' => 'Y'
	)
);?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>