<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Calendar\UserField\ResourceBooking;
use Bitrix\Crm\Integration\Calendar;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Loader;

class ResourceBookingField extends BaseSimpleField
{
	public const TYPE = 'resourcebooking';

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions): string
	{
		if (!Loader::includeModule('calendar') || !Calendar::isResourceBookingEnabled())
		{
			return parent::getFormattedValueForExport($fieldValue, $itemId, $displayOptions);
		}

		$results = [];

		if (!$this->isMultiple())
		{
			return $this->getPreparedValue($fieldValue, $itemId);
		}

		foreach ($fieldValue as $elementId)
		{
			$results[] = $this->getPreparedValue($fieldValue, $itemId);
		}

		return implode($displayOptions->getMultipleFieldsDelimiter(), $results);
	}

	protected function getPreparedValue($fieldValue, $itemId): string
	{
		$fieldValue = (array)$fieldValue;

		$userField = array_merge(
			$this->getUserFieldParams(),
			[
				'ENTITY_VALUE_ID' => $itemId,
				'VALUE' => $this->getPreparedArrayValues($fieldValue),
			]
		);

		return ResourceBooking::getPublicText($userField);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if (!Loader::includeModule('calendar') || !Calendar::isResourceBookingEnabled())
		{
			return [];
		}

		return [
			'value' => !empty($fieldValue) ? $this->getPreparedValue($fieldValue, $itemId) : '',
		];
	}
}
