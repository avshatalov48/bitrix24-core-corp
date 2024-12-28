<?php

namespace Bitrix\TasksMobile\UserField\Field;

class DoubleField extends BaseField
{
	protected function prepareSingleValue(string|null $value = ''): string
	{
		return (string)($value === '' || is_null($value) ? $this->settings['DEFAULT_VALUE'] : $value);
	}

	protected function prepareSettings(array $settings): array
	{
		$precision = (int)($settings['PRECISION'] ?? 0);

		return [
			'DEFAULT_VALUE' => $this->getDefaultValue($settings['DEFAULT_VALUE'], $precision),
			'MIN_VALUE' => (float)($settings['MIN_VALUE'] ?? 0),
			'MAX_VALUE' => (float)($settings['MAX_VALUE'] ?? 0),
			'PRECISION' => $precision,
		];
	}

	private function getDefaultValue(?string $defaultValue, int $precision): string
	{
		if (!$defaultValue || !is_numeric($defaultValue))
		{
			return '';
		}

		$formattedValue = number_format((float)$defaultValue, $precision, '.');

		if (str_contains($formattedValue, '.'))
		{
			$formattedValue = rtrim(rtrim($formattedValue, '0'), '.');
		}

		return $formattedValue;
	}
}
