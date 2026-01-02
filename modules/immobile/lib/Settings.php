<?php

namespace Bitrix\ImMobile;

use Bitrix\Im\V2\Application\Features;
use Bitrix\Main\Loader;

class Settings
{
	public static function isBetaAvailable(): bool
	{
		return \Bitrix\Main\Config\Option::get('immobile', 'beta_available', 'N') === 'Y';
	}

	public static function isChatLocalStorageAvailable(): bool
	{
		$isChatLocalStorageAvailable = \Bitrix\Main\Config\Option::get('immobile', 'chat_local_storage_available', 'Y') === 'Y';
		if (!$isChatLocalStorageAvailable)
		{
			return false;
		}

		if (!self::isSyncServiceEnabled())
		{
			return false;
		}

		return true;
	}

	public static function isSyncServiceEnabled(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		return \Bitrix\Im\V2\Sync\SyncService::isEnable();
	}

	public static function shouldShowChatV2UpdateHint(): bool
	{
		return \Bitrix\Main\Config\Option::get('immobile', 'should_show_chat_m1_update_hint', 'Y') === 'Y';
	}

	public static function planLimits(): ?array
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return null;
		}

		return \Bitrix\Im\V2\TariffLimit\Limit::getInstance()->getRestrictions();
	}

	public static function getImFeatures(): ?Features
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return null;
		}

		return Features::get();
	}

	public static function getMultipleActionMessageLimit(): ?int
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return null;
		}

		return \Bitrix\Im\V2\Message\MessageService::getMultipleActionMessageLimit();
	}

	public static function isTasksRecentListAvailable(): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return \Bitrix\Main\Config\Option::get('im', 'is_tasks_recent_list_available', 'N') === 'Y';
	}

	public static function isMessengerV2Enabled(): bool
	{
		if (\Bitrix\Main\Config\Option::get('immobile', 'messenger_v2_enabled', 'N') === 'Y')
		{
			return true;
		}

		if (self::isMessengerV2EnabledForCurrentUser())
		{
			return true;
		}

		return false;
	}

	public static function isMessengerV2EnabledForCurrentUser(): bool
	{
		return \CUserOptions::GetOption('immobile', 'messenger_v2_enabled', 'N') === 'Y';
	}

	public static function toggleMessengerV2ForCurrentUser(): array
	{
		$isSuccess = self::isMessengerV2EnabledForCurrentUser() ? self::disableMessengerV2ForCurrentUser() : self::enableMessengerV2ForCurrentUser();

		return [
			'isSuccess' => $isSuccess,
			'isMessengerV2Enabled' => self::isMessengerV2Enabled(),
			'isMessengerV2EnabledForCurrentUser' => self::isMessengerV2EnabledForCurrentUser(),
		];
	}

	public static function enableMessengerV2ForCurrentUser(): bool
	{
		return \CUserOptions::SetOption('immobile', 'messenger_v2_enabled', 'Y');
	}

	public static function disableMessengerV2ForCurrentUser(): bool
	{
		return \CUserOptions::SetOption('immobile', 'messenger_v2_enabled', 'N');
	}
}
