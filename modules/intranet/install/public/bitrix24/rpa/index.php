<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:rpa.router',
	'',
	array(
		'root' => '/rpa/',
	)
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
