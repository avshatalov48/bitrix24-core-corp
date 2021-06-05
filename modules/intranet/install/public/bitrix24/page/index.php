<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

/** @var \CMain $APPLICATION */

$APPLICATION->includeComponent(
	'bitrix:intranet.customsection',
	''
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
