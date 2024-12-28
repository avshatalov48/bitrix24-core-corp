<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/** @var CMain $APPLICATION */
$APPLICATION->includeComponent(
	'bitrix:crm.router',
	'',
	[
		'root' => '/crm/',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
