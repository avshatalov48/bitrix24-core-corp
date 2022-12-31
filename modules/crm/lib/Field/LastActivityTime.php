<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

final class LastActivityTime extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($item->isNew())
		{
			$this->setLastActivityValues($item, $item->getCreatedTime(), $item->getCreatedBy());

			return new Result();
		}

		$identifier = ItemIdentifier::createByItem($item);
		$monitor = Monitor::getInstance();

		if ($monitor->isTimelineChanged($identifier))
		{
			[$lastActivityTime, $lastActivityBy] = $monitor->calculateLastActivityInfo($identifier);

			$lastActivityTime ??= $item->getCreatedTime();
			$lastActivityBy ??= $item->getCreatedBy();

			$this->setLastActivityValues($item, $lastActivityTime, $lastActivityBy);
		}

		return new Result();
	}

	private function setLastActivityValues(
		Item $item,
		DateTime $lastActivityTime,
		int $lastActivityBy
	): void
	{
		$item->set($this->getName(), $lastActivityTime);

		if ($item->hasField(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$item->set(Item::FIELD_NAME_LAST_ACTIVITY_BY, $lastActivityBy);
		}
	}
}
