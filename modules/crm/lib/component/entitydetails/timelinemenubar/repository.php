<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Call;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Comment;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Delivery;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\EInvoiceApp;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Email;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\GoToChat;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Market;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Meeting;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\RestPlacement;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Sharing;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Sms;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Task;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\ToDo;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Visit;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Wait;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\WhatsApp;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Zoom;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Settings;
use Bitrix\Main\Loader;
use Bitrix\Rest\PlacementTable;

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
		return array_filter($this->getAllItems(), static fn(Item $item) => $item->isAvailable());
	}

	/**
	 * @return Item[]
	 */
	public function getAllItems(): array
	{
		$context = $this->context;
		
		$items = [
			new ToDo($context),
			new Comment($context),
			new Task($context),
			new Sharing($context),
		];

		if (Settings\Crm::isWhatsAppScenarioEnabled())
		{
			$items[] = new WhatsApp($context);
		}

		return array_merge($items, [
			new Sms($context),
			new GoToChat($context),
			new Email($context),
			new Delivery($context),
			new Wait($context),
			new Zoom($context),
			new Meeting($context),
			new Call($context),
			new Visit($context),
			...$this->getRestPlacementItems(),
			new EInvoiceApp($context),
			new Market($context),
		]);
	}

	protected function getRestPlacementItems(): array
	{
		$result = [];

		if (!Loader::includeModule('rest'))
		{
			return $result;
		}

		$placementCode = AppPlacement::getDetailActivityPlacementCode($this->context->getEntityTypeId());
		$placementHandlerList = PlacementTable::getHandlersList($placementCode);
		foreach ($placementHandlerList as $placementHandler)
		{
			$result[] = (new RestPlacement($this->context))
				->setAppId($placementHandler['APP_ID'] ?? '')
				->setAppName($placementHandler['APP_NAME'] ?? '')
				->setPlacementId($placementHandler['ID'] ?? '')
				->setPlacementTitle($placementHandler['TITLE'] ?? '')
				->setPlacementCode($placementCode)
				->setPlacementOptions($placementHandler['OPTIONS'] ?? [])
			;
		}

		return $result;
	}
}
