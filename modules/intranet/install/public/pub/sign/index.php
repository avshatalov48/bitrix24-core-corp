<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:signproxy.pub',
	'',
	array(
		'HASH' => $_REQUEST['hash'] ?? ''
	),
	null,
	array(
		'HIDE_ICONS' => 'Y'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');