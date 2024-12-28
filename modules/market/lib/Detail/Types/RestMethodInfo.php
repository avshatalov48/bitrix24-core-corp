<?php

namespace Bitrix\Market\Detail\Types;

use Bitrix\Market\Application\MarketDetail;

interface RestMethodInfo
{
	public function getMethodName(): string;

	public function getParams(MarketDetail $appInfo): array;
}