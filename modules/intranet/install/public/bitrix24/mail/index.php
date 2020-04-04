<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:mail.client',
	'',
	array(
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/mail/',
	)
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
