<?php

namespace Bitrix\Market\Detail\Types;

use Bitrix\Market\Application\MarketDetail;
use Bitrix\Market\Rest\Actions;

class SiteType implements RestMethodInfo
{
	public function getMethodName(): string
	{
		return Actions::METHOD_SITE_PREVIEW;
	}

	public function getParams(MarketDetail $appInfo): array
	{
		return [
			'code' => $appInfo->getAppCode(),
		];
	}
}