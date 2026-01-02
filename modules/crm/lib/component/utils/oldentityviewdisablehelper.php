<?php

namespace Bitrix\Crm\Component\Utils;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

final class OldEntityViewDisableHelper
{
	public const LAST_TIME_SHOWN_FIELD = 'old_layout_disable_alert_last_time_shown_date';
	public const LAST_TIME_SHOWN_OPTION_NAME = 'timestamp';
	private const DISABLE_DATE = 'old_layout_disable_date';
	private const DAYS_TO_NOTIFY_AGAIN = 7;

	public static function getDaysLeftUntilDisable(): int
	{
		$disableTimestamp = Option::get('crm', self::DISABLE_DATE, null);

		if ($disableTimestamp === null)
		{
			return 0;
		}

		$disableDate = DateTime::createFromTimestamp($disableTimestamp);
		$currentDate = (new DateTime())->toUserTime();

		return ($disableDate->getTimestamp() > $currentDate->getTimestamp())
			? ($currentDate->getDiff($disableDate))->days
			: 0
		;
	}

	public static function canShowAlert(): bool
	{
		if (LayoutSettings::getCurrent()->isSliderEnabled())
		{
			return false;
		}

		$lastTimeShownTimestamp = \CUserOptions::GetOption(
			'crm',
			self::LAST_TIME_SHOWN_FIELD,
			null,
		);

		if ($lastTimeShownTimestamp === null)
		{
			return true;
		}

		// Since js measures timestamps in ms and php in s, we have to normalize it
		$lastTimeShownTimestampNormalized = (int)($lastTimeShownTimestamp[self::LAST_TIME_SHOWN_OPTION_NAME] / 1000);

		$currentDate = (new DateTime())->toUserTime();
		$lastTimeShownDate = DateTime::createFromTimestamp($lastTimeShownTimestampNormalized);

		$diff = $currentDate->getDiff($lastTimeShownDate)->days;

		return $diff >= self::DAYS_TO_NOTIFY_AGAIN;
	}

	public static function migrateToNewLayout(): void
	{
		if (!LayoutSettings::getCurrent()->isSliderEnabled())
		{
			self::setNewCardLayout();
		}

		self::deleteUnusedOptions();
	}

	private static function setNewCardLayout(): void
	{
		LayoutSettings::getCurrent()->enableSlider(true);
	}

	private static function deleteUnusedOptions(): void
	{
		Option::delete('crm', ['name' => self::DISABLE_DATE]);
		\CUserOptions::DeleteOptionsByName('crm', self::LAST_TIME_SHOWN_FIELD);
	}
}
