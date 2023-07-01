<?php

namespace Bitrix\Market;

use Bitrix\Main\Localization\Loc;

class PricePolicy
{
	public const FREE = 'FREE';
	public const SUBSCRIPTION = 'SUBSCRIPTION';

	public static function getAll(): array
	{
		return [
			PricePolicy::FREE,
			PricePolicy::SUBSCRIPTION,
		];
	}

	public static function getByApp(array $app): string
	{
		if (isset($app['BY_SUBSCRIPTION']) && $app['BY_SUBSCRIPTION'] === 'Y') {
			return PricePolicy::SUBSCRIPTION;
		} else if (isset($app['FREE']) && $app['FREE'] == 'Y') {
			return PricePolicy::FREE;
		}

		return '';
	}

	public static function getName(string $pricePolicy): ?string
	{
		if (!in_array($pricePolicy, PricePolicy::getAll())) {
			return '';
		}

		return Loc::getMessage('MARKET_PRICE_POLICY_' . $pricePolicy);
	}
}