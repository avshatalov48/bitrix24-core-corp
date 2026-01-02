<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Main\Application;
use Bitrix\Main\DB\PgsqlConnection;

/**
 * @todo should be moved to
 * @see \Bitrix\Main\DB\SqlHelper::class
 */
class SqlHelper
{
	public function makeDateTimeFromTimestamp(int|string $timestamp): string
	{
		$timestamp = (string)$timestamp;

		if (Application::getConnection() instanceof PgsqlConnection)
		{
			return 'TO_TIMESTAMP(' . $timestamp . ')';
		}

		return 'FROM_UNIXTIME(' . $timestamp . ')';
	}
}
