<?php

namespace Bitrix\Crm\RepeatSale\Service\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Main\Result;

interface ActionInterface
{
	public function process(
		Item $clientItem,
		int $assignmentUserId,
		?Result $prevActionResult = null,
		?Context $context = null,
		?SegmentItem $segmentItem = null,
	): Result;
}
