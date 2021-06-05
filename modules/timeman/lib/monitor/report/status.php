<?php

namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\ArgumentException;
use CUserOptions;

class Status
{
	public const MODULE_ID = 'timeman';
	public const MONITOR_IS_HISTORY_SENT_OPTION = 'monitor_is_history_sent';

	public const CLOSED = true;
	public const WAITING_DATA = false;

	public static function setForCurrentUser($status): bool
	{
		if (!in_array($status, [self::CLOSED, self::WAITING_DATA], true))
		{
			throw new ArgumentException('Unsupported report status: ' . $status);
		}

		CUserOptions::SetOption(
			self::MODULE_ID,
			self::MONITOR_IS_HISTORY_SENT_OPTION,
			$status
		);

		return true;
	}

	public static function getForCurrentUser(): bool
	{
		return CUserOptions::GetOption(
			self::MODULE_ID,
			self::MONITOR_IS_HISTORY_SENT_OPTION,
			self::CLOSED
		);
	}

	public static function getForUser($userId): bool
	{
		return CUserOptions::GetOption(
			self::MODULE_ID,
			self::MONITOR_IS_HISTORY_SENT_OPTION,
			self::CLOSED,
			(int)$userId
		);
	}
}