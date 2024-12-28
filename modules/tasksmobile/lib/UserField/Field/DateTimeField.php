<?php

namespace Bitrix\TasksMobile\UserField\Field;

use Bitrix\Main\UserField\Types\DateType;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

class DateTimeField extends BaseField
{
	protected function prepareSingleValue(string|null $value = ''): string
	{
		if ($value === '' || is_null($value))
		{
			if ($this->settings['DEFAULT_VALUE'])
			{
				return $this->prepareSingleValue($this->settings['DEFAULT_VALUE']);
			}

			return '';
		}

		return (string)strtotime($value);
	}

	protected function prepareSettings(array $settings): array
	{
		return [
			'DEFAULT_VALUE' => $this->getDefaultValue($settings['DEFAULT_VALUE']),
			'USE_SECONDS' => $settings['USE_SECOND'] === 'Y',
			'USE_TIMEZONE' => $settings['USE_TIMEZONE'] === 'Y',
		];
	}

	private function getDefaultValue(array $defaultValue): string
	{
		$format = preg_replace('/:s$/', '', DateTime::convertFormatToPhp(FORMAT_DATETIME));
		$nowTimestamp = time();
		$fixedTimestamp = strtotime($defaultValue['VALUE']) - User::getTimeZoneOffsetCurrentUser();

		return match ($defaultValue['TYPE']) {
			DateType::TYPE_NOW => DateTime::createFromTimestamp($nowTimestamp)->format($format),
			DateType::TYPE_FIXED => DateTime::createFromTimestamp($fixedTimestamp)->format($format),
			default => '',
		};
	}
}
