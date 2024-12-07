<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:calendar.open-events',
	'',
	[],
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>
