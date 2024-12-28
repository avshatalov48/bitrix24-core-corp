<?php

namespace Bitrix\Timeman\Integration\Stafftrack;

use Bitrix\Main;
use Bitrix\Stafftrack;

class Counter
{
	public static function get(): array
	{
		$isNeededToShow = self::isNeededToShow();

		if (!$isNeededToShow || !CheckIn::isEnabled())
		{
			return [
				'CLASS' => '',
				'VALUE' => '',
			];
		}

		return [
			'CLASS' => 'ui-counter-danger',
			'VALUE' => '1',
		];
	}

	protected static function isNeededToShow(): bool
	{
		if (!CheckIn::isEnabled())
		{
			return false;
		}

		$userId = Main\Engine\CurrentUser::get()?->getId();
		if (!$userId)
		{
			return false;
		}

		return StaffTrack\Provider\CounterProvider::getInstance()->isNeededToShow($userId);
	}
}
