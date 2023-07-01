<?php

namespace Bitrix\Market;

use Bitrix\Main\Context;

class History
{
	public static function getFirstPageInfo($skeleton): array
	{
		return [
			'uri' => Context::getCurrent()->getRequest()->getRequestUri(),
			'skeleton' => $skeleton,
		];
	}
}