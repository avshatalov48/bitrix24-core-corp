<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Event;
use Bitrix\Main\Result;

class SendEvent extends Action
{
	protected $eventName;

	public function __construct(string $eventName)
	{
		parent::__construct();

		$this->eventName = $eventName;
	}

	public function process(Item $item): Result
	{
		$params = [
			'item' => $item,
		];
		$id = $item->getId();
		$beforeSaveItem = $this->getItemBeforeSave();
		if (!$id && $beforeSaveItem)
		{
			$id = $beforeSaveItem->getId();
		}
		$params['id'] = $id;
		$event = new Event('crm', $this->eventName, $params);

		$event->send();

		return new Result();
	}
}
