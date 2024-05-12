<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return;
}

return [
	'bitrixCallsEnabled' => \Bitrix\Im\Call\Call::isBitrixCallEnabled(),
];
