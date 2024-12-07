<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification;

use Bitrix\Crm\Item;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Integration\Im\ProcessEntity\Notification;
use Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class WhenUpdatingEntity extends ProcessSendNotification
{
	protected const SENDING_TYPE = Notification::UPDATE_SENDING_TYPE;

	public function process(Item $item): Result
	{
		if (!$this->getItemBeforeSave())
		{
			return (new Result())
				->addError(new Error('itemBeforeSave is required in ' . static::class))
			;
		}

		return parent::process($item);
	}

	protected function getPreparedDifference(Item $item): Difference
	{
		return ComparerBase::compareEntityFields(
			$this->getItemBeforeSave()?->getData(Values::ACTUAL),
			$item->getData(),
		);
	}
}
