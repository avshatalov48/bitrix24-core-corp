<?php

namespace Bitrix\Tasks\Integration\Bizproc\Flow\Robot;

use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\Config\Where;
use Bitrix\Tasks\Integration\Bizproc\Document\Flow;
use Bitrix\Tasks\Integration\Bizproc\Document\Task;

class Factory
{
	public static function getDocumentType(Item $item): array
	{
		switch ($item->getWhen()->getType())
		{
			case When::SLOW_QUEUE:
			case When::BUSY_RESPONSIBLE:
			case When::SLOW_EFFICIENCY:
			case When::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION:
			case When::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT:
			case When::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE:
			case When::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT:
				return ['tasks', Flow::class, 'FLOW'];
			default:
				return ['tasks', Task::class, 'TASK'];
		}
	}

	public static function buildRobots(Item $item): array
	{
		switch ($item->getWhere()->getValue())
		{
			case Where::NOTIFICATION_CENTER:
				return (new SocnetMessage())->build($item);
			default:
				return [];
		}
	}
}