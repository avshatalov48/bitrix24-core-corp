<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
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

		/** @var DateTime|null $previousTime */
		$previousTime = $item->remindActual($this->getName());
		if (!$previousTime)
		{
			return \Bitrix\Crm\Result::fail('Previous last activity time is not set');
		}

		/** @var DateTime|null $currentTime */
		$currentTime = $item->get($this->getName());
		if (!$currentTime)
		{
			$this->resetLastActivityValues($item);

			return new Result();
		}

		if ($currentTime->getTimestamp() <= $previousTime->getTimestamp())
		{
			$this->resetLastActivityValues($item);

			return new Result();
		}

		return new Result();
	}

	private function setLastActivityValues(
		Item $item,
		DateTime $lastActivityTime,
		int $lastActivityBy,
	): void
	{
		$item->set($this->getName(), $lastActivityTime);

		if ($item->hasField(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$item->set(Item::FIELD_NAME_LAST_ACTIVITY_BY, $lastActivityBy);
		}
	}

	private function resetLastActivityValues(Item $item): void
	{
		$item->reset($this->getName());

		if ($item->hasField(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$item->reset(Item::FIELD_NAME_LAST_ACTIVITY_BY);
		}
	}
}
