<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\ActivityTable;
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
			$activity = ActivityTable::query()
				->where('ID', $item['ACTIVITY_ID'])
				->setSelect([
					'ID',
					'COMPLETED',
					'RESPONSIBLE_ID',
					'DEADLINE',
				])
				->fetch()
			;

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
			$deadline = $activity['DEADLINE'] ?? null;
			if ($deadline && \CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
			{
				$deadline = null;
			}

			foreach ($bindings as $binding)
			{
				static::addPing((int)$item['ACTIVITY_ID'], $item['PING_DATETIME'], $deadline, $binding, $authorId);

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

	private static function addPing(int $activityId, DateTime $created, ?DateTime $deadline, array $binding, ?int $authorId): void
	{
		$settings = [];
		if ($created && $deadline)
		{
			$settings['PING_OFFSET'] = $deadline->getTimestamp() - $created->getTimestamp();
		}

		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
				'ENTITY_ID' => $binding['OWNER_ID'],
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'CREATED' => $created,
				'SETTINGS' => $settings,
			],
			LogMessageType::PING,
			$authorId
		);
	}
}
