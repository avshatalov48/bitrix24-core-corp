<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork;

use Bitrix\Crm\Item;
use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Integration\Im\ProcessEntity\NotificationManager;
use Bitrix\Main\Result;

abstract class ProcessSendNotification extends Action
{
	protected const SENDING_TYPE = '';

	public function process(Item $item): Result
	{
		NotificationManager::getInstance()->sendAllNotifications(
			$item->getEntityTypeId(),
			$this->getPreparedDifference($item),
			static::SENDING_TYPE,
		);

		return new Result();
	}

	abstract protected function getPreparedDifference(Item $item): Difference;
}
