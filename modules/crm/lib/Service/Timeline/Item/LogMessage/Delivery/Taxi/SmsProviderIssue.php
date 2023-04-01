<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery\Taxi;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class SmsProviderIssue extends Delivery
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_SMS_PROVIDER_ISSUE_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_STATUS_TAG_ERROR'),
				Tag::TYPE_FAILURE
			),
		];
	}

	protected function getContentText(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_SMS_PROVIDER_ISSUE_CONTENT');
	}
}
