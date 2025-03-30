<?php

namespace Bitrix\Call;

use Bitrix\Main\Config\Option;
use Bitrix\Im;

class Settings
{
	public static function getMobileOptions(): array
	{
		return array_merge([
			'useCustomTurnServer' => Option::get('im', 'turn_server_self') === 'Y',
			'turnServer' => Option::get('im', 'turn_server', ''),
			'turnServerLogin' => Option::get('im', 'turn_server_login', ''),
			'turnServerPassword' => Option::get('im', 'turn_server_password', ''),
			'callLogService' => Option::get('im', 'call_log_service', ''),
			'sfuServerEnabled' => Im\Call\Call::isCallServerEnabled(),
			'bitrixCallsEnabled' => Im\Call\Call::isBitrixCallEnabled(),
			'callBetaIosEnabled' => Im\Call\Call::isIosBetaEnabled(),
			'isAIServiceEnabled' => static::isAIServiceEnabled(),
		], self::getAdditionalMobileOptions());
	}

	// todo should be moved to callmobile along with the rest of the parameters
	protected static function getAdditionalMobileOptions(): array
	{
		\Bitrix\Main\Loader::includeModule('im');

		$userId = (int)$GLOBALS['USER']->getId();
		$usersData = \Bitrix\Im\Call\Util::getUsers([$userId]);

		return [
			'currentUserData' => $usersData[$userId],
		];
	}

	public static function isConferenceChatEnabled(): bool
	{
		return (bool)Option::get('call', 'conference_chat_enabled', true);
	}

	/**
	 * Call AI feature is enabled.
	 * @return bool
	 */
	public static function isAIServiceEnabled(): bool
	{
		return (bool)Option::get('call', 'call_ai_enabled', false);
	}

	public static function useTcpSdp(string $region = ''): string
	{
		if (
			($value = Option::get('call', 'call_use_tcp_sdp', null))
			&& in_array($value, ['N', 'Y'])
		)
		{
			return $value;
		}

		/*
		return match (\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: $region)
		{
			'ru' => 'Y',
			default => 'N',
		};
		*/
		return 'N';
	}

	/**
	 * User control feature is enabled.
	 * @return bool
	 */
	public static function isUserControlFeatureEnabled(): bool
	{
		if (Option::get('call', 'call_user_control_enabled', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_user_control_enabled', false);
	}

	/**
	 * Picture in picture feature is enabled.
	 * @return bool
	 */
	public static function isPictureInPictureFeatureEnabled(): bool
	{
		if (Option::get('call', 'call_picture_in_picture_enabled', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_picture_in_picture_enabled', false);
	}

	/**
	 * New QOS is enabled.
	 * @return bool
	 */
	public static function isNewQOSEnabled(): bool
	{
		if (Option::get('call', 'call_new_qos_enabled', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_new_qos_enabled', false);
	}
}
