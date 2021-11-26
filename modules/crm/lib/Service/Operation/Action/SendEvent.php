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
		$event = new Event('crm', $this->eventName, [
			'item' => $item,
		]);

		$event->send();

		return new Result();
	}
}