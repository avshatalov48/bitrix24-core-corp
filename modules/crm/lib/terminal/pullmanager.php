<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Pull\Event;
use Bitrix\Pull\Model\WatchTable;

class PullManager
{
	public const MODULE_ID = 'crm';
	public const COMMAND = 'CRM_TERMINAL';

	private const LIST_EVENT_ITEM_ADDED = 'ADDED';
	private const LIST_EVENT_ITEM_UPDATED = 'UPDATED';
	private const LIST_EVENT_ITEM_DELETED = 'DELETED';

	public static function subscribe(int $userId): void
	{
		if (!self::includePullModule())
		{
			return;
		}

		if ($userId <= 0)
		{
			return;
		}

		\CPullWatch::Add($userId, self::COMMAND);
	}

	public static function add(array $ids)
	{
		self::sendEvent(
			self::LIST_EVENT_ITEM_ADDED,
			$ids
		);
	}

	public static function update(array $ids)
	{
		self::sendEvent(
			self::LIST_EVENT_ITEM_UPDATED,
			$ids
		);
	}

	public static function delete(array $ids)
	{
		self::sendEvent(
			self::LIST_EVENT_ITEM_DELETED,
			$ids
		);
	}

	private static function sendEvent(string $eventName, array $ids): void
	{
		if (!self::includePullModule())
		{
			return;
		}

		$userIds = self::getSubscribedUserIds();
		if (empty($userIds))
		{
			return;
		}

		$currentUser = CurrentUser::get()->getId();
		unset($userIds[$currentUser]);

		Event::add(
			$userIds,
			[
				'module_id' => self::MODULE_ID,
				'command' => self::COMMAND,
				'params' => [
					'eventName' => $eventName,
					'items' => array_map(
						static function ($id) {
							return [
								'id' => $id,
							];
						},
						$ids
					),
				],
			]
		);
	}

	private static function getSubscribedUserIds(): array
	{
		if (!self::includePullModule())
		{
			return [];
		}

		return WatchTable::getUserIdsByTag(
			self::COMMAND
		);
	}

	private static function includePullModule(): bool
	{
		return Loader::includeModule('pull');
	}
}
