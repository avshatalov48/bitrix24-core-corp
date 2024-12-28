<?php

namespace Bitrix\TasksMobile\UserField\Field;

use Bitrix\Main\Localization\Loc;
use Bitrix\TasksMobile\UserField\Field\BooleanField\DisplayType;

class BooleanField extends BaseField
{
	protected function prepareSingleValue(string|null $value = ''): string
	{
		return ($value === '' || is_null($value) ? $this->settings['DEFAULT_VALUE'] : $value);
	}

	protected function prepareSettings(array $settings): array
	{
		return [
			'DEFAULT_VALUE' => (string)$settings['DEFAULT_VALUE'],
			'YES_LABEL' => $this->getYesLabel($settings),
			'NO_LABEL' => $this->getNoLabel($settings),
			'DISPLAY_TYPE' => DisplayType::tryFrom($settings['DISPLAY'] ?? DisplayType::Checkbox->value),
		];
	}

	private function getYesLabel(array $settings): string
	{
		if (!empty($settings['LABEL']) && is_array($settings['LABEL']))
		{
			$yesLabel = (string)($settings['LABEL'][1] ?? '');
			if ($yesLabel !== '')
			{
				return $yesLabel;
			}
		}

		return Loc::getMessage('TASKS_MOBILE_USER_FIELD_FIELD_BOOLEAN_YES');
	}

	private function getNoLabel(array $settings): string
	{
		if (!empty($settings['LABEL']) && is_array($settings['LABEL']))
		{
			$noLabel = (string)($settings['LABEL'][0] ?? '');
			if ($noLabel !== '')
			{
				return $noLabel;
			}
		}

		return Loc::getMessage('TASKS_MOBILE_USER_FIELD_FIELD_BOOLEAN_NO');
	}
}
