<?php

namespace Bitrix\Intranet\Binding;

use Bitrix\Main\Loader;

class Marketplace
{
	public static function getMainDirectory(): string
	{
		if (Loader::includeModule('market'))
		{
			return '/market/';
		}

		return '/marketplace/';
	}

	public static function getBoxMainDirectory(): string
	{
		if (Loader::includeModule('market'))
		{
			return 'market/';
		}

		return 'marketplace/';
	}
}