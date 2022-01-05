<?php

namespace Bitrix\Tasks\Scrum\Utility;

class TimeHelper
{
	private $userId;

	private $offsets = [];

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
		if (!isset($this->offsets[$userId]))
		{
			$this->offsets[$userId] = \CTimeZone::getOffset($userId, true);
		}

		return $this->offsets[$userId];
	}
}