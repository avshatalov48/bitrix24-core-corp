<?php

namespace Bitrix\StaffTrack\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\StaffTrack\Dictionary\Option;
use Bitrix\StaffTrack\Model\OptionTable;
use Bitrix\StaffTrack\Trait\Singleton;

class OptionService
{
	use Singleton;

	/**
	 * @param int $userId
	 * @param Option $option
	 * @param string $value
	 * @return void
	 */
	public function save(int $userId, Option $option, string $value): void
	{
		$insertFields = [
			'USER_ID' => $userId,
			'NAME' => $option->value,
			'VALUE' => $value
		];

		$updateFields = [
			'VALUE' => $value,
		];

		$uniqueFields = ['USER_ID', 'NAME'];

		OptionTable::merge($insertFields, $updateFields, $uniqueFields);
	}

	/**
	 * @param int $userId
	 * @param Option $option
	 * @return void
	 * @throws ArgumentException
	 */
	public function delete(int $userId, Option $option): void
	{
		OptionTable::deleteByFilter([
			'USER_ID' => $userId,
			'NAME' => $option->value,
		]);
	}
}