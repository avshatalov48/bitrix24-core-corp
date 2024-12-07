<?php

/** @var $APPLICATION \CMain */
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:humanresources.start',
	'',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/humanresources/',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
