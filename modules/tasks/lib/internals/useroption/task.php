<?php
namespace Bitrix\Tasks\Internals\UserOption;

use Bitrix\Main;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\UserOption;

/**
 * Class Task
 *
 * @package Bitrix\Tasks\Internals\UserOption
 */
class Task
{
	/**
	 * @param int $userId
	 * @param int $option
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getByUserIdAndOption(int $userId, int $option): array
	{
		$tasks = [];

		if (!$userId || !UserOption::isOption($option))
		{
			return $tasks;
		}

		$optionsResult = UserOptionTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'USER_ID' => $userId,
				'OPTION_CODE' => $option,
			],
		]);
		while ($data = $optionsResult->fetch())
		{
			$tasks[] = (int)$data['TASK_ID'];
		}

		return $tasks;
	}
}