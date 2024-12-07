<?php

namespace Bitrix\Market\Application;

use Bitrix\Rest\AppTable;

class Installed
{
	public static function getByCode(string $appCode): array
	{
		$result = AppTable::getRow([
			'filter' => [
				'=CODE' => $appCode,
			]
		]);

		return $result ?: [];
	}
}