<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Crm\Service\Timeline\Item\Factory;

class TimelineItemManager
{
	use Singleton;

	private Factory\HistoryItem $itemFactory;

	public function __construct()
	{
		 $this->itemFactory = Container::getInstance()->getTimelineHistoryItemFactory();
	}

	public function addAssociatedEntityDataToTimelineRows(array $rows, UserPermissions $userPermissions): array
	{
		TimelineManager::prepareDisplayData(
			$rows,
			$userPermissions->getUserId(),
			null,
			false,
			['type' => Context::REST]
		);

		return $rows;
	}

	public function makeTimelineItem(array $timelineRow, UserPermissions $userPermissions, ItemIdentifier $identifier): Item
	{
		$context = new Context(
			$identifier,
			Context::REST,
			$userPermissions->getUserId(),
		);
		return $this->itemFactory::createItem($context, $timelineRow);
	}
}