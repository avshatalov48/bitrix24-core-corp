<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

class DateTimeField extends DateField
{
	public const TYPE = 'datetime';

	/**
	 * @return array|string
	 */
	protected function getDefaultDatetimeFormat()
	{
		return CCrmDateTimeHelper::getDefaultDateTimeFormat();
	}

	protected function getPreparedConfig(): array
	{
		return [
			'enableTime' => true,
			'checkTimezoneOffset' => true,
		];
	}
}
