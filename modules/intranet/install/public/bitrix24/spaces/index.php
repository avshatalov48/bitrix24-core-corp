<?php

/** @var $APPLICATION \CMain */

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->IncludeComponent(
	'bitrix:socialnetwork.spaces',
	'',
	[
		'SEF_FOLDER' => '/spaces/',
	]
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';