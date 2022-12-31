<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Model\ActivityPingQueueTable;
use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Type\DateTime;
use CCrmActivity;
use CCrmOwnerType;

class PingAgent extends AgentBase
{
	private const DEADLINE_HIGH_BOUND_LENGTH = 20; // in seconds

	public static function doRun(): bool
	{
		$highBound = (new DateTime())->add('+' . self::DEADLINE_HIGH_BOUND_LENGTH . ' seconds');
		$result = ActivityPingQueueTable::getList([
			'select' => ['*'],
			'filter' => [
				'<=PING_DATETIME' => $highBound,
			],
			'order' => ['PING_DATETIME' => 'ASC']
		])->fetchAll();

		if (empty($result))
		{
			return true; // nothing to do
		}

		foreach ($result as $item)
		{
			$activity = CCrmActivity::GetByID($item['ACTIVITY_ID'], false);
			$bindings = CCrmActivity::GetBindings($item['ACTIVITY_ID']);
			if (
				!is_array($activity)
				|| !(is_array($bindings) && !empty($bindings))
				|| static::isActivityPassed($activity)
			)
			{
				ActivityPingQueueTable::delete($item['ID']);

				continue;
			}

			$authorId = $activity['RESPONSIBLE_ID'] ?? null;

			foreach ($bindings as $binding)
			{
				static::addPing((int)$item['ACTIVITY_ID'], $item['PING_DATETIME'], $binding, $authorId);

				ActivityPingQueueTable::delete($item['ID']);
			}
		}

		return true;
	}

	private static function isActivityPassed($activity): bool
	{

		if (!is_array($activity))
		{
			return true; // no activity
		}

		if (isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
		{
			return true;
		}

		return false;
	}

	private static function addPing(int $activityId, DateTime $created, array $binding, ?int $authorId): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
				'ENTITY_ID' => $binding['OWNER_ID'],
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'CREATED' => $created,
			],
			LogMessageType::PING,
			$authorId
		);
	}
}
