<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Main\Type\DateTime;

class DateTimeField extends DateField
{
	public const TYPE = 'datetime';

	/**
	 * @return array|string
	 */
	protected function getDefaultDatetimeFormat()
	{
		$layoutSettings = LayoutSettings::getCurrent();
		if ($layoutSettings && $layoutSettings->isSimpleTimeFormatEnabled())
		{
			return [
				'tommorow' => 'tommorow',
				's' => 'sago',
				'i' => 'iago',
				'H3' => 'Hago',
				'today' => 'today',
				'yesterday' => 'yesterday',
				//'d7' => 'dago',
				'-' => DateTime::convertFormatToPhp(FORMAT_DATE),
			];
		}

		return preg_replace(
			'/:s$/',
			'',
			DateTime::convertFormatToPhp(FORMAT_DATETIME)
		);
	}

	protected function getPreparedConfig(): array
	{
		return [
			'enableTime' => true,
			'checkTimezoneOffset' => true,
		];
	}
}
