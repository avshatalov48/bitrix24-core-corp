<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration;


class Transcription
{
	/** @var array<array{start: string, end: string, userId: int, user: string, text: string}> */
	public array $transcriptions = [];

	public bool $isEmpty = true;

	public function __construct(?Integration\AI\Outcome $outcome = null)
	{
		if ($outcome)
		{
			$transcriptions = $outcome->getProperty('transcriptions')?->getStructure();
			if (is_array($transcriptions))
			{
				$users = [];
				foreach ($transcriptions as $row)
				{
					$row['text'] = trim($row['text']);
					if (empty($row['text']))
					{
						continue;
					}
					$obj = new \stdClass;
					$obj->userId = (int)$row['user_id'];

					if (!isset($users[$obj->userId]))
					{
						$user = \Bitrix\Im\User::getInstance($obj->userId);
						$users[$obj->userId] = $user->getFullName(false) ?: "User{$obj->userId}";
					}
					$obj->start = $row['start_time_formatted'];
					$obj->end = $row['end_time_formatted'];
					$obj->user = $users[$obj->userId];
					$obj->text = $row['text'];

					$this->transcriptions[] = $obj;
				}
				$this->isEmpty = empty($this->transcriptions);
			}
		}

		return $this;
	}
}