<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery\Taxi;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class CancelledByManager extends Delivery
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_CANCELLED_BY_MANAGER_TITLE');
	}

	protected function getContentText(): ?string
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');
		$fields = is_array($fields) ? $fields : [];

		$isPaid = isset($fields['IS_PAID']) && $fields['IS_PAID'];
		if (!$isPaid)
		{
			return null;
		}

		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_CANCELLED_BY_MANAGER_CONTENT_PAID');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_STATUS_TAG_CANCELLATION'),
				Tag::TYPE_WARNING
			),
		];
	}
}
