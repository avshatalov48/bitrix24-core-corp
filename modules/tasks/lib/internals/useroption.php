<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\Util\Result;
use Exception;
use ReflectionClass;

/**
 * Class UserOption
 *
 * @package Bitrix\Tasks\Internals
 */
class UserOption
{
	/**
	 * @param int $userId
	 * @param int $option
	 * @return string
	 */
	public static function getSelectSql(int $userId, int $option): string
	{
		$selectSql = '';

		if (!$userId || !static::isOption($option))
		{
			return $selectSql;
		}

		$tableName = UserOptionTable::getTableName();
		$select = "SELECT 'x' FROM {$tableName} WHERE TASK_ID = T.ID AND USER_ID = {$userId} AND OPTION_CODE = {$option}";

		return "IF(EXISTS({$select}), 'Y', 'N')";
	}

	/**
	 * @param int $userId
	 * @param int $option
	 * @param string $aliasPrefix
	 * @return string
	 */
	public static function getFilterSql(int $userId, int $option, $aliasPrefix = ''): string
	{
		$filterSql = '';

		if (!$userId || !static::isOption($option))
		{
			return $filterSql;
		}

		$tableName = UserOptionTable::getTableName();

		return "IN (
			SELECT {$aliasPrefix}TUO.TASK_ID FROM {$tableName} {$aliasPrefix}TUO
			WHERE
				{$aliasPrefix}TUO.OPTION_CODE = {$option}
				AND {$aliasPrefix}TUO.TASK_ID = {$aliasPrefix}T.ID
				AND {$aliasPrefix}TUO.USER_ID = {$userId}
		)";
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function isOptionSet(int $taskId, int $userId, int $option): bool
	{
		return static::isOption($option) && in_array($option, static::getOptions($taskId, $userId), true);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOptions(int $taskId, int $userId): array
	{
		$optionsResult = UserOptionTable::getList([
			'select' => ['OPTION_CODE'],
			'filter' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
			],
		]);

		$options = [];
		while ($option = $optionsResult->fetch())
		{
			$options[] = (int)$option['OPTION_CODE'];
		}

		return $options;
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Exception
	 */
	public static function add(int $taskId, int $userId, int $option): Result
	{
		static::onBeforeOptionChanged($taskId, $userId, $option);

		$addResult = new Result();

		if ($taskId <= 0 || $userId <= 0 || !static::isOption($option))
		{
			$addResult->addError(0, 'Some parameter is wrong.');
			return $addResult;
		}

		$data = [
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
			'OPTION_CODE' => $option,
		];

		$item = UserOptionTable::getList([
			'select' => ['ID'],
			'filter' => $data,
		])->fetch();

		if (!$item)
		{
			$tableAddResult = UserOptionTable::add($data);
			if (!$tableAddResult->isSuccess())
			{
				$addResult->addError(2, 'Adding to table failed.');
				return $addResult;
			}

			static::onOptionChanged($taskId, $userId, $option, true);

			return $addResult;
		}

		$addResult->addError(1, 'This option for task and user is already exist.');

		return $addResult;
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Exception
	 */
	public static function delete(int $taskId, int $userId, int $option): Result
	{
		static::onBeforeOptionChanged($taskId, $userId, $option);

		$deleteResult = new Result();

		if ($taskId <= 0 || $userId <= 0 || !static::isOption($option))
		{
			$deleteResult->addError(0, 'Some parameter is wrong.');
			return $deleteResult;
		}

		$item = UserOptionTable::getList([
			'select' => ['ID'],
			'filter' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'OPTION_CODE' => $option,
			],
		])->fetch();

		if ($item)
		{
			$tableDeleteResult = UserOptionTable::delete($item);
			if (!$tableDeleteResult->isSuccess())
			{
				$deleteResult->addError(1, 'Deleting from table failed.');
				return $deleteResult;
			}
			static::onOptionChanged($taskId, $userId, $option, false);
		}

		return $deleteResult;
	}

	/**
	 * @param int $taskId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByTaskId(int $taskId): void
	{
		if ($taskId > 0)
		{
			UserOptionTable::deleteByTaskId($taskId);
		}
	}


	/**
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByUserId(int $userId): void
	{
		if ($userId > 0)
		{
			UserOptionTable::deleteByUserId($userId);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByTaskIdAndUserId(int $taskId, int $userId): void
	{
		if ($taskId > 0 && $userId > 0)
		{
			UserOptionTable::deleteByTaskIdAndUserId($taskId, $userId);
		}
	}

	/**
	 * @param int $option
	 * @return bool
	 */
	public static function isOption(int $option): bool
	{
		return in_array($option, static::getAllowedOptions(), true);
	}

	/**
	 * @return array
	 */
	public static function getAllowedOptions(): array
	{
		$allowedOptions = [];

		$reflect = new ReflectionClass(Option::class);
		foreach ($reflect->getConstants() as $option)
		{
			$allowedOptions[] = $option;
		}

		return $allowedOptions;
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 */
	public static function onBeforeOptionChanged(int $taskId, int $userId, int $option): void
	{
		if ($option === Option::MUTED)
		{
			Counter\CounterService::getInstance()->collectData($taskId);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 * @param bool $added
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onOptionChanged(int $taskId, int $userId, int $option, bool $added): void
	{
		if ($taskId <= 0 || $userId <= 0 || !static::isOption($option))
		{
			return;
		}

		if ($option === Option::MUTED)
		{
			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_TASK_MUTE,
				[
					'TASK_ID' => $taskId,
					'USER_ID' => $userId,
					'ADDED' => $added
				]
			);
		}

		static::sendPushOptionChanged($taskId, $userId, $option, $added);

		$event = new Main\Event(
			'tasks',
			'onTaskUserOptionChanged',
			[
				'taskId' => $taskId,
				'userId' => $userId,
				'option' => $option,
				'added' => $added,
			]
		);
		$event->send();
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $option
	 * @param bool $added
	 * @throws Main\LoaderException
	 */
	private static function sendPushOptionChanged(int $taskId, int $userId, int $option, bool $added): void
	{
		PushService::addEvent([$userId], [
			'module_id' => 'tasks',
			'command' => 'user_option_changed',
			'params' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'OPTION' => $option,
				'ADDED' => $added,
			],
		]);
	}
}