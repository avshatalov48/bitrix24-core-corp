<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Extranet;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class ExtranetService
{
	public function isExtranet(): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			$extranetSiteId = Option::get('extranet', 'extranet_site');

			return $extranetSiteId === SITE_ID;
		}

		return \CExtranet::IsExtranetSite();
	}
}
