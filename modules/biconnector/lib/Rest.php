<?php

namespace Bitrix\BIConnector;

use Bitrix\Main\Loader;

if (!Loader::includeModule('rest'))
{
	return;
}

class Rest extends \IRestService
{
	const BI_MENU_PLACEMENT = 'BI_ANALYTICS_MENU';

	public static function onRestServiceBuildDescription(): array
	{
		return [
			\CRestUtil::GLOBAL_SCOPE => [
				\CRestUtil::PLACEMENTS => [
					static::BI_MENU_PLACEMENT => [],
				],
			],
		];
	}
}
