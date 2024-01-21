<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class DateField extends BaseField
{
	public const TYPE = 'date';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (!$this->value)
		{
			return null;
		}

		$result = [];

		$formattedValue = $this->value;
		if (!is_array($formattedValue))
		{
			$formattedValue = [$formattedValue];
		}

		foreach ($formattedValue as $value)
		{
			$result[] = $this->getTimestamp($value);
		}

		return $this->isMultiple() ? $result : $result[0];
	}

	/**
	 * @param $value
	 * @return int
	 */
	private function getTimestamp($value): int
	{
		$timeZoneOffset = \CTimeZone::GetOffset();
		if ($value instanceof DateTime)
		{
			$useTimezone = ($this->getUserFieldInfo()['SETTINGS']['USE_TIMEZONE'] ?? 'Y') === 'Y';
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

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = parent::getData();

		$data['enableTime'] = false;

		return $data;
	}
}
