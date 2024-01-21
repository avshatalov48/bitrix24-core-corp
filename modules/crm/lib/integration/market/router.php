<?php

namespace Bitrix\Crm\Integration\Market;

use Bitrix\Main\Loader;

class Router
{
	public static function getBasePath(): string
	{
		if (Loader::includeModule('intranet'))
		{
			return
				Loader::includeModule('bitrix24')
					? \Bitrix\Intranet\Binding\Marketplace::getMainDirectory()
					: (SITE_DIR ?: '/') . \Bitrix\Intranet\Binding\Marketplace::getBoxMainDirectory();
		}

		if (Loader::includeModule('market'))
		{
			return (SITE_DIR ?: '/') . 'market/';
		}

		return (SITE_DIR ?: '/') . 'marketplace/';
	}

	public static function getCategoryPath(string $categoryCode, array $context = []): string
	{
		return self::getBasePath() . "category/$categoryCode/" . (empty($context) ? ''  : '?' . http_build_query($context));
	}

	public static function getApplicationPath(string $appCode): string
	{
		return self::getBasePath() . "detail/$appCode/";
	}
}
