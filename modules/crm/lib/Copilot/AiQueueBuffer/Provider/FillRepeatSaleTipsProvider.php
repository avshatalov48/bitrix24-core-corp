<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Provider;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class FillRepeatSaleTipsProvider implements QueueBufferProviderInterface
{
	public static function getId(): int
	{
		return 1;
	}

	public function process(?array $data = null): Result
	{
		$result = new Result();

		if (empty($data))
		{
			return $result->addError(
				new Error(
					'Provider data must be specified',
					'PROVIDER_DATA_EMPTY'
				)
			);
		}

		$activityId = (int)($data['activityId'] ?? 0);
		if ($activityId <= 0)
		{
			return $result->addError(
				new Error(
					'Activity ID  must be specified',
					'ACTIVITY_ID_EMPTY'
				)
			);
		}

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		$userId = $activity['RESPONSIBLE_ID'] ?? null;

		return AIManager::launchFillRepeatSaleTips($activityId, $userId);
	}
}
