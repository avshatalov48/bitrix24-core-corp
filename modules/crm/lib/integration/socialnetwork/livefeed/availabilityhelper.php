<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;

class AvailabilityHelper
{
	private const OPTION_MODULE = 'crm';
	private const LF_IN_CRM_ENABLED = 'LIVE_FEED_IN_CRM_AVAILABLE';
	private const LF_IN_CRM_USED = 'LIVE_FEED_IN_CRM_USED';
	private const LF_DISABLE_DATE = 'LIVE_FEED_IN_CRM_DISABLE_DATE';

	public const ALERT_SELECTOR_CLASS = 'crm-disable-lf-alert';
	public const SHOW_ALERT_OPTION = 'show_alert_about_disabling_livefeed';

	public static function isAvailable(): bool
	{
		return (
			Loader::includeModule('socialnetwork')
			&& (bool)Option::get(self::OPTION_MODULE, self::LF_IN_CRM_ENABLED, true)
		);
	}

	public static function setAvailable(bool $isAvailable): void
	{
		Option::set(self::OPTION_MODULE, self::LF_IN_CRM_ENABLED, $isAvailable);
	}

	public static function isUsed(): bool
	{
		return (
			Loader::includeModule('socialnetwork')
			&& (bool)Option::get(self::OPTION_MODULE, self::LF_IN_CRM_USED, false)
		);
	}

	public static function setUsed(bool $isUsed): void
	{
		Option::set(self::OPTION_MODULE, self::LF_IN_CRM_USED, $isUsed);
	}

	public static function getDaysUntilDisable(): ?int
	{
		$disableTimestamp = self::getDisableTimestamp();
		if (is_null($disableTimestamp))
		{
			return null;
		}

		$disableDate = Date::createFromTimestamp($disableTimestamp);
		$currentDate = new Date();

		return ($disableDate->getTimestamp() >= $currentDate->getTimestamp())
			? $currentDate->getDiff($disableDate)->days
			: 0
		;
	}

	public static function getDisableTimestamp(): ?int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$disableTimestamp = Option::get(self::OPTION_MODULE, self::LF_DISABLE_DATE, null);
		if (is_null($disableTimestamp))
		{
			return null;
		}

		return $disableTimestamp;
	}

	public static function setDisableTimestamp(int $disableTimestamp): void
	{
		Option::set(self::OPTION_MODULE, self::LF_DISABLE_DATE, $disableTimestamp);
	}

	public static function isShowAlert(): bool
	{
		$options = \CUserOptions::GetOption('crm', self::SHOW_ALERT_OPTION, ['show' => 'Y']);

		return
			self::isAvailable()
			&& self::isUsed()
			&& $options['show'] === 'Y'
			&& Loader::includeModule('socialnetwork')
			&& !is_null(self::getDisableTimestamp())
		;
	}

	public static function deleteUnusedOptions(): void
	{
		Option::delete('crm', ['name' => self::LF_DISABLE_DATE]);
		Option::delete('crm', ['name' => self::LF_IN_CRM_USED]);

		\CUserOptions::DeleteOptionsByName('crm', self::SHOW_ALERT_OPTION);
	}
}
