<?php

namespace Bitrix\Crm\RepeatSale\Segment;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SegmentItemChecker
{
	use Singleton;

	private ?SegmentItem $item = null;

	public function setItem(?SegmentItem $item): self
	{
		$this->item = $item;

		return $this;
	}

	public function setItemByActivity(array $activity): self
	{
		$segmentId = (int)($activity['PROVIDER_PARAMS']['SEGMENT_ID'] ?? 0);
		$entity = RepeatSaleSegmentController::getInstance()->getById($segmentId, true);
		if ($entity)
		{
			$this->item = SegmentItem::createFromEntity($entity);
		}

		return $this;
	}

	public function run(): Result
	{
		$result = new Result();

		$checker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if (!$checker->isEnabled())
		{
			return $result->addError(new Error(
				Loc::getMessage('CRM_SEGMENT_ITEM_REPEAT_SALE_OFF'),
				'CRM_REPEAT_SALE_REPEAT_SALE_OFF',
			));
		}

		if (!$checker->hasPermission())
		{
			return $result->addError(new Error(
				Loc::getMessage('CRM_SEGMENT_ITEM_REPEAT_SALE_ACCESS_DENIED'),
				ErrorCode::ACCESS_DENIED,
			));
		}

		if (!$this->item)
		{
			return $result->addError(new Error(
				Loc::getMessage('CRM_SEGMENT_ITEM_NOT_FOUND'),
				ErrorCode::NOT_FOUND,
			));
		}

		if ($this->isTitleEmpty() || $this->isPromptEmpty())
		{
			return $result->addError(new Error(
				Loc::getMessage('CRM_SEGMENT_ITEM_INVALID'),
				ErrorCode::INVALID_ARG_VALUE,
			));
		}

		return $result; // success
	}

	private function isTitleEmpty(): bool
	{
		return empty(trim($this->item->getTitle()));
	}

	private function isPromptEmpty(): bool
	{
		return empty(trim($this->item->getPrompt()));
	}
}
