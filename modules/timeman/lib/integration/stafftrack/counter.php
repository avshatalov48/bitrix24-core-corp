<?php

namespace Bitrix\Timeman\Integration\Stafftrack;

use Bitrix\Main;
use Bitrix\Stafftrack;

class Counter
{
	public static function get(): array
	{
		$hasCounter = self::hasCounter();
		$isNeededToShow = self::isNeededToShow();
		$isNew = !$hasCounter && !$isNeededToShow;

		if (!CheckIn::isEnabled() || (!$isNeededToShow && !$isNew))
		{
			return [
				'CLASS' => '',
				'VALUE' => '',
			];
		}

		$text = $isNew ? Main\Localization\Loc::getMessage('TM_INTEGRATION_STAFFTRACK_NEW') : '1';
		$color = $isNew ? 'ui-counter-primary' : 'ui-counter-danger';

		return [
			'CLASS' => $color,
			'VALUE' => $text,
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

	protected static function hasCounter(): bool
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

		return StaffTrack\Provider\CounterProvider::getInstance()->get($userId) !== null;
	}
}
