<?php

use Bitrix\Booking\Internals\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$baseCurrencyId = '';
if (\Bitrix\Main\Loader::includeModule('booking'))
{
	$provider = Container::getProviderManager()::getCurrentProvider();
	$baseCurrencyId = $provider?->getDataProvider()?->getBaseCurrencyId() ?? '';
}

$currencies = [];
if (\Bitrix\Main\Loader::includeModule('currency'))
{
	$currencyIterator = \Bitrix\Currency\CurrencyTable::getList([
		'select' => ['CURRENCY'],
	]);

	while (['CURRENCY' => $currency] = $currencyIterator->fetch())
	{
		$currencies[] = [
			'CURRENCY' => $currency,
			'FORMAT' => \CCurrencyLang::GetFormatDescription($currency),
		];
	}
}

return [
	'css' => 'dist/currency-format.bundle.css',
	'js' => 'dist/currency-format.bundle.js',
	'rel' => [
		'main.core',
		'currency.currency-core',
	],
	'skip_core' => false,
	'settings' => [
		'baseCurrencyId' => $baseCurrencyId,
		'currencies' => $currencies,
	],
];
