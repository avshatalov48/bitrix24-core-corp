<?php

use \Bitrix\Location\Service\AddressService;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if(!\Bitrix\Main\Loader::includeModule('location'))
{
	throw new \Bitrix\Main\SystemException('Module Location not installed');
}

return [
	'js' => './dist/core.bundle.js',
	'oninit' => static function()
	{
		return [
			'lang_additional' => [
				'LOCATION_IS_ADDRESS_LIMIT_REACHED' => AddressService::getInstance()->isLimitReached()
			]
		];
	}
];