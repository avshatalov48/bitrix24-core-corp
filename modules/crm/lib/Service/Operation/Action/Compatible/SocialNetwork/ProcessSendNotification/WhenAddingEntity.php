<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification;

use Bitrix\Crm\Item;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Integration\Im\ProcessEntity\Notification;
use Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification;

class WhenAddingEntity extends ProcessSendNotification
{
	protected const SENDING_TYPE = Notification::ADD_SENDING_TYPE;

	protected function getPreparedDifference(Item $item): Difference
	{
		return ComparerBase::compareEntityFields([], $item->getData());
	}
}
