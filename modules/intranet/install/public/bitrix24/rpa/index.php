<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:rpa.router',
	'',
	array(
		'root' => SITE_DIR . 'rpa/',
	)
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
