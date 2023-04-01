<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery\Taxi;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Delivery;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

class EstimationRequest extends Delivery
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_ESTIMATION_REQUEST_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');
		$fields = is_array($fields) ? $fields : [];

		$content = Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_ESTIMATION_REQUEST_CONTENT');
		if (isset($fields['EXPECTED_PRICE_DELIVERY']))
		{
			$content .= ': ' . (string)$fields['EXPECTED_PRICE_DELIVERY'];
		}

		$result = [
			'content' => (new Text())->setValue($content),
		];

		if (isset($fields['ADDRESS_FROM']))
		{
			$result['addressFrom'] =
				(new ContentBlockWithTitle())
					->setTitle(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_ESTIMATION_REQUEST_CONTENT_FROM')
					)
					->setContentBlock(
						(new Text())
							->setValue((string)$fields['ADDRESS_FROM'])
					)
			;
		}

		if (isset($fields['ADDRESS_TO']))
		{
			$result['addressTo'] =
				(new ContentBlockWithTitle())
					->setTitle(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DELIVERY_TAXI_ESTIMATION_REQUEST_CONTENT_TO')
					)
					->setContentBlock(
						(new Text())
							->setValue((string)$fields['ADDRESS_TO'])
					)
			;
		}

		return $result;
	}
}
