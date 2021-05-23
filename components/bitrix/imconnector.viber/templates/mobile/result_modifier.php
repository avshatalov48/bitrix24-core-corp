<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arResult['URI_DOMAIN'] = \Bitrix\ImConnector\Connector::getDomainDefault();

if(defined('BX24_HOST_NAME'))
	$arResult['URI_DOMAIN_MOBILE'] = "bitrix24://" . BX24_HOST_NAME;
else
	$arResult['URI_DOMAIN_MOBILE'] = "bitrix24://" . \Bitrix\Main\Context::getCurrent()->getServer()->getServerName();