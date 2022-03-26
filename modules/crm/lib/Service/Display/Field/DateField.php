<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class DateField extends BaseSimpleField
{
	protected const TYPE = 'date';

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isUserField())
		{
			return parent::getFormattedValueForGrid($fieldValue, $itemId, $displayOptions);
		}

		return $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
	}

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isUserField())
		{
			return parent::getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
		}

		$displayParams = $this->getDisplayParams();
		$format = ($displayParams['DATETIME_FORMAT'] ?? $this->getDefaultDatetimeFormat());
		$timeZoneOffset = \CTimeZone::GetOffset();

		if (!$this->isMultiple())
		{
			return $this->getPreparedValue($fieldValue, $format, $timeZoneOffset);
		}

		$results = [];
		foreach ($fieldValue as $value)
		{
			$results[] = $this->getPreparedValue($value, $format, $timeZoneOffset);
		}

		return $results;
	}

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions)
	{
		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		return implode($displayOptions->getMultipleFieldsDelimiter(), $fieldValue);
	}

	protected function getDefaultDatetimeFormat()
	{
		return DateTime::convertFormatToPhp(FORMAT_DATE);
	}

	protected function getPreparedValue($value, $format, $timeZoneOffset): string
	{
		if ($value instanceof DateTime || $value instanceof \DateTime)
		{
			$timestamp = $value->getTimestamp() + $timeZoneOffset;
		}
		elseif ($value instanceof Date)
		{
			$timestamp = $value->getTimestamp();
		}
		else
		{
			$timestamp = \MakeTimeStamp($value);
		}

		return \FormatDate($format, $timestamp, time() + $timeZoneOffset);
	}
}
