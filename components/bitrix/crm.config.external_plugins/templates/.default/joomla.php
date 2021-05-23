<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$uri = new \Bitrix\Main\Web\Uri($arResult['REQUEST']->getRequestUri());
$arParams['APP_URL'] = 'https://extensions.joomla.org/extensions/extension/communication/live-support/bitrix24/';

include 'detail.php';