<?php

namespace Bitrix\Tasks\Scrum\Utility;

class TimeHelper
{
	private $userId;

	private static $offsets = [];

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getCurrentOffsetUTC(): int
	{
		return (int) (date('Z') + $this->getOffset($this->userId));
	}

	private function getOffset($userId = false)
	{
		if (!isset(self::$offsets[$userId]))
		{
			self::$offsets[$userId] = \CTimeZone::getOffset($userId, true);
		}

		return self::$offsets[$userId];
	}
}