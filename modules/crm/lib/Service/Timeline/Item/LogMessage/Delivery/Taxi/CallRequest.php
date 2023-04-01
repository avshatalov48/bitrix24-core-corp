<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery\Taxi;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Main\Localization\Loc;

class CallRequest extends Delivery
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_CALL_REQUEST_TITLE');
	}

	protected function getContentText(): ?string
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');
		$fields = is_array($fields) ? $fields : [];

		$result = Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_CALL_REQUEST_CONTENT');
		if (isset($fields['EXPECTED_PRICE_DELIVERY']))
		{
			$result .= ': ' . (string)$fields['EXPECTED_PRICE_DELIVERY'];
		}

		return $result;
	}
}
