<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery\Taxi;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class ReturnedFinish extends Delivery
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_RETURNED_FINISH_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_STATUS_TAG_RETURN'),
				Tag::TYPE_WARNING
			),
		];
	}
}
