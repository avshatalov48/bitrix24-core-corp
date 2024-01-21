<?php

namespace Bitrix\ImMobile;

class Settings
{
	public static function isBetaAvailable(): bool
	{
		return \Bitrix\Main\Config\Option::get('immobile', 'beta_available', 'N') === 'Y';
	}

	public static function isChatM1Enabled(): bool
	{
		return !self::isLegacyChatEnabled();
	}

	public static function isLegacyChatEnabled(): bool
	{
		if (\Bitrix\Main\Config\Option::get('immobile', 'legacy_chat_enabled', 'N') === 'Y')
		{
			return true;
		}

		if (\CUserOptions::GetOption('immobile', 'legacy_chat_user_enabled', 'N') === 'Y')
		{
			return true;
		}

		return false;
	}

	public static function isChatLocalStorageAvailable(): bool
	{
		$isAvailable = \Bitrix\Main\Config\Option::get('immobile', 'chat_local_storage_available', 'Y') === 'Y';
		if (!$isAvailable)
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
}
