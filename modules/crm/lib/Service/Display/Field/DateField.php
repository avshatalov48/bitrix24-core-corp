<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class DateField extends BaseSimpleField
{
	public const TYPE = 'date';

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

		$timeZoneParams = $this->getTimeZoneParams();

		if (!$this->isMultiple())
		{
			return $this->getPreparedValue($fieldValue, $timeZoneParams['format'], $timeZoneParams['offset']);
		}

		$results = [];
		foreach ($fieldValue as $value)
		{
			$results[] = $this->getPreparedValue($value, $timeZoneParams['format'], $timeZoneParams['offset']);
		}

		return $results;
	}

	protected function getTimeZoneParams(): array
	{
		$displayParams = $this->getDisplayParams();
		$format = ($displayParams['DATETIME_FORMAT'] ?? $this->getDefaultDatetimeFormat());
		$offset = \CTimeZone::GetOffset();

		return [
			'format' => $format,
			'offset' => $offset,
		];
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$result = [
			'config' => $this->getPreparedConfig(),
		];

		if (!empty($fieldValue))
		{
			$timeZoneParams = $this->getTimeZoneParams();
			$values = [];

			if (!is_array($fieldValue))
			{
				$fieldValue = [$fieldValue];
			}

			foreach ($fieldValue as $value)
			{
				$values[] = $this->getTimestampForMobile($value, $timeZoneParams['offset']);
			}

			$result['value'] = $this->isMultiple() ? $values : ($values[0] ?? null);
		}

		return $result;
	}

	/**
	 * @param $value
	 * @param int $timeZoneOffset
	 * @return int
	 */
	protected function getTimestampForMobile($value, int $timeZoneOffset): int
	{
		if ($value instanceof DateTime)
		{
			$useTimezone = ($this->getUserFieldParams()['SETTINGS']['USE_TIMEZONE'] ?? 'Y') === 'Y';
			if (!$useTimezone)
			{
				return $value->getTimestamp() - $timeZoneOffset;
			}

			return $value->getTimestamp();
		}

		if ($value instanceof Date)
		{
			return $value->getTimestamp() - $timeZoneOffset;
		}

		if ($value instanceof \DateTime)
		{
			return $value->getTimestamp() - $timeZoneOffset;
		}

		return \MakeTimeStamp($value) - $timeZoneOffset;
	}

	protected function getPreparedConfig(): array
	{
		return [
			'enableTime' => false,
			'checkTimezoneOffset' => true,
		];
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
		$timestamp = $this->getTimestamp($value, $timeZoneOffset);

		return \FormatDate($format, $timestamp, time() + $timeZoneOffset);
	}

	/**
	 * @param $value
	 * @param int $timeZoneOffset
	 * @return int
	 */
	protected function getTimestamp($value, int $timeZoneOffset): int
	{
		if ($value instanceof DateTime || $value instanceof \DateTime)
		{
			return $value->getTimestamp() + $timeZoneOffset;
		}

		if ($value instanceof Date)
		{
			return $value->getTimestamp();
		}

		return \MakeTimeStamp($value);
	}
}
