<?php
namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Tasks\Integration\Forum\Task\UserTopic;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Project;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;

/**
 * Class EventListener
 *
 * @package Bitrix\Tasks\Integration\SocialNetwork
 */
class EventListener
{
	/**
	 * @param $id
	 * @param $arFields
	 */
	public static function onGroupPermissionUpdate($id, $arFields): void
	{
		Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_PROJECT_PERM_UPDATE, [
			'FEATURE_PERM' => $id
		]);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 */
	public static function onGroupAdd(int $id, array $fields): void
	{
		$fields['ID'] = $id;

		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_ADD,
			$fields
		);
		ProjectLastActivityTable::tryToAdd($id);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 */
	public static function onBeforeGroupUpdate(int $id, array $fields): void
	{
		$fields['ID'] = $id;

		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_BEFORE_UPDATE,
			$fields
		);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 */
	public static function onGroupUpdate(int $id, array $fields): void
	{
		$fields['ID'] = $id;

		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_UPDATE,
			$fields
		);
	}

	/**
	 * @param $groupId
	 */
	public static function onGroupDelete($groupId): void
	{
		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_PROJECT_DELETE,
			['GROUP_ID' => $groupId]
		);
		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_REMOVE,
			['ID' => $groupId]
		);
		ProjectUserOptionTable::deleteByProjectId($groupId);
		ProjectLastActivityTable::delete($groupId);
	}

	/**
	 * @param $id
	 * @param $arFields
	 */
	public static function onGroupUserAdd($id, $arFields): void
	{
		if (
			array_key_exists('USER_ID', $arFields)
			&& array_key_exists('GROUP_ID', $arFields)
		)
		{
			$userId = (int) $arFields['USER_ID'];
			$groupId = (int) $arFields['GROUP_ID'];

			if ($groupId > 0)
			{
				UserTopic::readGroups($userId, [$groupId], true);
				ViewedTable::readGroups($userId, [$groupId], true);
			}

			Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_PROJECT_USER_ADD, [
				'GROUP_ID' => $groupId,
				'USER_ID' => $userId,
			]);

			Project\Event\EventHandler::addEvent(
				Project\Event\EventTypeDictionary::EVENT_PROJECT_USER_ADD,
				$arFields
			);
		}
	}

	/**
	 * @param $id
	 * @param $arFields
	 * @param $oldFields
	 */
	public static function onGroupUserUpdate($id, $arFields, $oldFields): void
	{
		if (
			array_key_exists('USER_ID', $arFields)
			&& array_key_exists('GROUP_ID', $arFields)
		)
		{
			Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_PROJECT_USER_UPDATE, [
				'GROUP_ID' => (int) $arFields['GROUP_ID'],
				'USER_ID' => (int) $arFields['USER_ID'],
			]);
		}

		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_USER_UPDATE,
			$oldFields
		);
	}

	/**
	 * @param $id
	 * @param $arFields
	 */
	public static function onGroupUserDelete($id, $arFields): void
	{
		if (
			array_key_exists('USER_ID', $arFields)
			&& array_key_exists('GROUP_ID', $arFields)
		)
		{
			Counter\CounterService::addEvent(Counter\Event\EventDictionary::EVENT_PROJECT_USER_DELETE, [
				'GROUP_ID' => (int) $arFields['GROUP_ID'],
				'USER_ID' => (int) $arFields['USER_ID'],
			]);
		}

		Project\Event\EventHandler::addEvent(
			Project\Event\EventTypeDictionary::EVENT_PROJECT_USER_REMOVE,
			$arFields
		);
	}

}