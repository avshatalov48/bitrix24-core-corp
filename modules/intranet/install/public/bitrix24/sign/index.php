<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->setTitle('Sign');

$APPLICATION->includeComponent(
	'bitrix:sign.start',
	'',
	array(
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/sign/'
	),
	null
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');