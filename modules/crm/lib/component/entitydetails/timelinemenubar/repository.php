<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Call;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Comment;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Delivery;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Email;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Market;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Meeting;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\RestPlacement;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Sms;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Task;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Sharing;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\ToDo;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Visit;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Wait;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Zoom;

final class Repository
{
	private Context $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	/**
	 * @return Item[]
	 */
	public function getAvailableItems(): array
	{
		return array_filter($this->getAllItems(), function(Item $item) {
			return $item->isAvailable();
		});
	}

	/**
	 * @return Item[]
	 */
	public function getAllItems(): array
	{
		$items = [
			new ToDo($this->context),
			new Comment($this->context),
			new Task($this->context),
			new Sharing($this->context),
			new Sms($this->context),
			new Email($this->context),
			new Delivery($this->context),
			new Wait($this->context),
			new Zoom($this->context),
			new Meeting($this->context),
			new Call($this->context),
			new Visit($this->context),
		];
		$items = array_merge(
			$items,
			$this->getRestPlacementItems(),
		);

		$items[] = new Market($this->context);

		return $items;
	}

	protected function getRestPlacementItems(): array
	{
		$result = [];
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return $result;
		}

		$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(
			\Bitrix\Crm\Integration\Rest\AppPlacement::getDetailActivityPlacementCode($this->context->getEntityTypeId())
		);
		foreach($placementHandlerList as $placementHandler)
		{
			$result[] = (new RestPlacement($this->context))
				->setAppId($placementHandler['APP_ID'] ?? '')
				->setAppName($placementHandler['APP_NAME'] ?? '')
				->setPlacementId($placementHandler['ID'] ?? '')
				->setPlacementTitle($placementHandler['TITLE'] ?? '')
			;
		}

		return $result;
	}
}
