<?php

namespace Bitrix\StaffTrack\Service;

use Bitrix\Main\Type\DateTime;
use Bitrix\StaffTrack\Dictionary\Mute;
use Bitrix\StaffTrack\Model\CounterTable;
use Bitrix\StaffTrack\Trait\Singleton;

class CounterService
{
	use Singleton;

	/**
	 * @param int $userId
	 * @param Mute $mute
	 * @param DateTime $muteUntil
	 * @return void
	 */
	public function save(int $userId, Mute $mute, DateTime $muteUntil = new DateTime()): void
	{
		$insertFields = [
			'USER_ID' => $userId,
			'MUTE_STATUS' => $mute->value,
			'MUTE_UNTIL' => $muteUntil,
		];

		$updateFields = [
			'MUTE_STATUS' => $mute->value,
			'MUTE_UNTIL' => $muteUntil,
		];

		$uniqueFields = ['USER_ID'];

		CounterTable::merge($insertFields, $updateFields, $uniqueFields);
	}
}