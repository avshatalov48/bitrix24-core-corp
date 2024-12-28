<?php

namespace Bitrix\TasksMobile\UserField\Field;

class StringField extends BaseField
{
	protected function prepareSingleValue(string|null $value = ''): string
	{
		$preparedValue = ($value === '' || is_null($value) ? $this->settings['DEFAULT_VALUE'] : $value);

		if ($this->settings['MIN_LENGTH'] > 0 && mb_strlen($preparedValue) < $this->settings['MIN_LENGTH'])
		{
			return '';
		}

		if ($this->settings['MAX_LENGTH'] > 0 && mb_strlen($preparedValue) > $this->settings['MAX_LENGTH'])
		{
			$preparedValue = mb_substr($preparedValue, 0, $this->settings['MAX_LENGTH']);
		}

		if ($this->settings['REGEXP'] && !preg_match($this->settings['REGEXP'], $preparedValue))
		{
			return '';
		}

		return $preparedValue;
	}

	protected function prepareSettings(array $settings): array
	{
		return [
			'DEFAULT_VALUE' => (string)($settings['DEFAULT_VALUE'] ?? ''),
			'MIN_LENGTH' => (int)($settings['MIN_LENGTH'] ?? 0),
			'MAX_LENGTH' => (int)($settings['MAX_LENGTH'] ?? 0),
			'REGEXP' => (string)($settings['REGEXP'] ?? ''),
		];
	}
}
