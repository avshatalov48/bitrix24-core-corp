<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\V2\Internal\Exception\Task\UserOptionException;
use Bitrix\Tasks\V2\Internal\Service\Task\UserOptionService;
use Bitrix\Tasks\V2\FormV2Feature;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Exception;
use ReflectionClass;

/**
 * Class UserOption
 *
 * @package Bitrix\Tasks\Internals
 */
class UserOption
{
	public static $cache = [];

	public const REMOVE_ON_USER_ROLE_CHANGED = [
		Option::MUTED,
	];

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

		return 'case when EXISTS(' . $select . ') then \'Y\' else \'N\' end';
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
	 * @deprecated
	 * @TasksV2
	 * @use UserRepositoryInterface
	 */
	public static function isOptionSet(int $taskId, int $userId, int $option): bool
	{
		if (FormV2Feature::isOn('option'))
		{
			return Container::getInstance()->getTaskUserOptionRepository()->isSet($option, $taskId, $userId);
		}

		return static::isOption($option) && in_array($option, static::getOptions($taskId, $userId), true);
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use UserRepositoryInterface
	 */
	public static function getOptions(int $taskId, int $userId): array
	{
		if (FormV2Feature::isOn('option'))
		{
			return Container::getInstance()->getTaskUserOptionRepository()->get($taskId, $userId)->getCodeList();
		}

		if (
			array_key_exists($userId, self::$cache)
			&& is_array(self::$cache[$userId])
			&& array_key_exists($taskId, self::$cache[$userId])
		)
		{
			return self::$cache[$userId][$taskId];
		}

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

		self::$cache[$userId][$taskId] = $options;

		return self::$cache[$userId][$taskId];
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use UserOptionService
	 */
	public static function add(int $taskId, int $userId, int $option): Result
	{
		if (FormV2Feature::isOn('option'))
		{
			$legacyResult = new Result();

			$service = Container::getInstance()->getUserOptionService();
			try
			{
				if ($option === Option::MUTED)
				{
					$service->mute(
						taskId: $taskId,
						userId: $userId
					);
				}
				elseif ($option === Option::PINNED)
				{
					$service->pin(
						taskId: $taskId,
						userId: $userId
					);
				}
				elseif ($option === Option::PINNED_IN_GROUP)
				{
					$service->pinInGroup(
						taskId: $taskId,
						userId: $userId
					);
				}

				return $legacyResult;
			}
			catch (Exception $e)
			{
				if ($e instanceof UserOptionException)
				{
					$legacyResult->addError(2, 'Adding to table failed.');
				}

				$legacyResult->addError(0, 'Some parameter is wrong.');
				$legacyResult->addError(2, $e->getMessage());

				return $legacyResult;
			}
		}

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
		// we no longer display this error
		// $addResult->addError(1, 'This option for task and user is already exist.');

		return $addResult;
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use UserOptionService
	 */
	public static function delete(int $taskId, int $userId, int $option): Result
	{
		if (FormV2Feature::isOn('option'))
		{
			$legacyResult = new Result();

			$service = Container::getInstance()->getUserOptionService();
			try
			{
				if ($option === Option::MUTED)
				{
					$service->unmute(
						taskId: $taskId,
						userId: $userId
					);
				}
				elseif ($option === Option::PINNED)
				{
					$service->unpin(
						taskId: $taskId,
						userId: $userId
					);
				}
				elseif ($option === Option::PINNED_IN_GROUP)
				{
					$service->unpinInGroup(
						taskId: $taskId,
						userId: $userId
					);
				}

				return $legacyResult;
			}
			catch (Exception $e)
			{
				if ($e instanceof UserOptionException)
				{
					$legacyResult->addError(1, 'Deleting to table failed.');
				}

				$legacyResult->addError(0, 'Some parameter is wrong.');
				$legacyResult->addError(1, $e->getMessage());

				return $legacyResult;
			}
		}

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
	 * @deprecated
	 * @TasksV2
	 * @use UserRepositoryInterface
	 */
	public static function deleteByTaskId(int $taskId): void
	{
		if (FormV2Feature::isOn('option'))
		{
			Container::getInstance()->getTaskUserOptionRepository()->delete(taskId: $taskId);

			return;
		}

		if ($taskId > 0)
		{
			UserOptionTable::deleteByTaskId($taskId);
			static::invalidate();
		}
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use UserRepositoryInterface
	 */
	public static function deleteByTaskIdAndUserId(int $taskId, int $userId): void
	{
		if (FormV2Feature::isOn('option'))
		{
			Container::getInstance()->getTaskUserOptionRepository()->delete(taskId: $taskId, userId: $userId);

			return;
		}

		if ($taskId > 0 && $userId > 0)
		{
			UserOptionTable::deleteByTaskIdAndUserId($taskId, $userId);
			static::invalidate($userId, $taskId);
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

		static::invalidate($userId, $taskId);

		if ($option === Option::MUTED)
		{
			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_TASK_MUTE,
				[
					'TASK_ID' => $taskId,
					'USER_ID' => $userId,
					'ADDED' => $added,
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
	 * @deprecated
	 * @TasksV2
	 * @use UserRepositoryInterface
	 *
	 * Removes necessary options when changing the user's role in a task
	 */
	public static function deleteOnUserRoleChanged(int $taskId, int $userId): void
	{
		if (FormV2Feature::isOn('option'))
		{
			Container::getInstance()->getTaskUserOptionRepository()->delete(
				codes: static::REMOVE_ON_USER_ROLE_CHANGED,
				taskId: $taskId,
				userId: $userId,
			);

			return;
		}

		if ($taskId <= 0 || $userId <= 0)
		{
			return;
		}

		UserOptionTable::deleteByOptions($taskId, $userId, static::REMOVE_ON_USER_ROLE_CHANGED);
		static::invalidate($userId, $taskId);
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
			'command' => PushCommand::USER_OPTION_UPDATED,
			'params' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'OPTION' => $option,
				'ADDED' => $added,
			],
		]);
	}

	private static function invalidate(int $userId = 0, int $taskId = 0): void
	{
		if ($taskId > 0)
		{
			unset(static::$cache[$userId][$taskId]);
		}
		elseif ($userId > 0)
		{
			unset(static::$cache[$userId]);
		}
		else
		{
			static::$cache = [];
		}
	}
}
