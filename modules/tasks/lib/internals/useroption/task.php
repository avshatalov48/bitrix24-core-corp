<?php
namespace Bitrix\Tasks\Internals\UserOption;

use Bitrix\Main;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\UserOption;
use Throwable;

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

	/**
	 * Handles mute option on task add.
	 *
	 * @param array $fields - task data
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onTaskAdd(array $fields): void
	{
		$taskId = (int)$fields['ID'];

		$usersExceptAuditors = array_unique(
			array_merge(
				[$fields['CREATED_BY'], $fields['RESPONSIBLE_ID']],
				(array)$fields['ACCOMPLICES']
			)
		);

		$autoMuteService = Main\DI\ServiceLocator::getInstance()->get('tasks.user.option.automute.service');
		$disabledAutoMuteUsers = $autoMuteService->getDisabledAutoMuteUsers();

		foreach ($fields['AUDITORS'] as $userId)
		{
			if (!in_array($userId, $usersExceptAuditors) && !in_array($userId, $disabledAutoMuteUsers))
			{
				UserOption::add($taskId, $userId, Option::MUTED);
			}
		}
	}

	/**
	 * Handles mute option on task update.
	 *
	 * @param array $oldFields - task data before update
	 * @param array $newFields - task data after update
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onTaskUpdate(array $oldFields, array $newFields): void
	{
		$taskId = (int)$newFields['ID'];

		$oldCreator = $oldFields['CREATED_BY'];
		$oldResponsible = $oldFields['RESPONSIBLE_ID'];
		$oldAccomplices = (array)$oldFields['ACCOMPLICES'];
		$oldAuditors = (array)$oldFields['AUDITORS'];

		$oldParticipants = array_unique(
			array_merge(
				[$oldCreator, $oldResponsible],
				$oldAccomplices,
				$oldAuditors
			)
		);

		$newCreator = ($newFields['CREATED_BY'] ?? null);
		$newResponsible = ($newFields['RESPONSIBLE_ID'] ?? null);
		$newAccomplices = ($newFields['ACCOMPLICES'] ?? null);
		$newAuditors = ($newFields['AUDITORS'] ?? null);

		$newParticipants = array_unique(
			array_merge(
				[($newCreator ?? $oldCreator), ($newResponsible ?? $oldResponsible)],
				(isset($newAccomplices) ? (array)$newAccomplices : $oldAccomplices),
				(isset($newAuditors) ? (array)$newAuditors : $oldAuditors)
			)
		);
		$newAccomplices = (isset($newAccomplices) ? (array)$newAccomplices : []);
		$newAuditors = (isset($newAuditors) ? (array)$newAuditors : []);

		$addedParticipants = array_unique(array_diff($newParticipants, $oldParticipants));
		$removedParticipants = array_unique(array_diff($oldParticipants, $newParticipants));

		$newUsersExceptAuditors = $newAccomplices;
		if (isset($newCreator))
		{
			$newUsersExceptAuditors[] = $newCreator;
		}
		if (isset($newResponsible))
		{
			$newUsersExceptAuditors[] = $newResponsible;
		}
		$newUsersExceptAuditors = array_unique($newUsersExceptAuditors);

		$autoMuteService = Main\DI\ServiceLocator::getInstance()->get('tasks.user.option.automute.service');
		$disabledAutoMuteUsers = $autoMuteService->getDisabledAutoMuteUsers();

		// new user for task was added directly to auditors
		foreach ($addedParticipants as $userId)
		{
			if (
				in_array($userId, $newAuditors)
				&& !in_array($userId, $newUsersExceptAuditors)
				&& !in_array($userId, $disabledAutoMuteUsers)
			)
			{
				UserOption::add($taskId, $userId, Option::MUTED);
			}
		}

		// removed users from task
		foreach ($removedParticipants as $userId)
		{
			UserOption::deleteByTaskIdAndUserId($taskId, $userId);
		}

		// user was removed from auditors and added to another role
		$removedAuditors = array_unique(array_diff($oldAuditors, $newAuditors));
		foreach ($removedAuditors as $userId)
		{
			if (in_array($userId, $newUsersExceptAuditors))
			{
				UserOption::deleteOnUserRoleChanged($taskId, $userId);
			}
		}

		// user was not removed from auditors but added to another role
		foreach ($newAuditors as $userId)
		{
			if (in_array($userId, $newUsersExceptAuditors))
			{
				UserOption::deleteOnUserRoleChanged($taskId, $userId);
			}
		}
	}

	protected static function getExceptMuteAuditors(): array
	{
		$value = Main\Config\Option::get('tasks', 'tasks_except_mute_auditors');

		try
		{
			$auditors = unserialize($value, ['allowed_classes' => false]);
			if (is_array($auditors))
			{
				return $auditors;
			}

			return [];
		}
		catch (Throwable)
		{
			return [];
		}
	}
}