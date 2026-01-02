<?php

namespace Bitrix\Call;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Im;

class Settings
{
	public static function getMobileOptions(): array
	{
		return array_merge([
			'useCustomTurnServer' => Option::get('call', 'turn_server_self') === 'Y',
			'turnServer' => \Bitrix\Im\Call\Call::getTurnServer(),
			'turnServerLogin' => Option::get('call', 'turn_server_login', ''),
			'turnServerPassword' => Option::get('call', 'turn_server_password', ''),
			'callLogService' => Option::get('call', 'call_log_service', ''),
			'sfuServerEnabled' => Im\Call\Call::isCallServerEnabled(),
			'bitrixCallsEnabled' => Im\Call\Call::isBitrixCallEnabled(),
			'callBetaIosEnabled' => Im\Call\Call::isIosBetaEnabled(),
			'isAIServiceEnabled' => static::isAIServiceEnabled(),
			'isNewMobileGridEnabled' => static::isNewMobileGridEnabled(),
			'userJwt' => JwtCall::getUserJwt((int)CurrentUser::get()->getId()),
			'callBalancerUrl' => static::getBalancerUrl(),
			'jwtCallsEnabled' => static::isNewCallsEnabled(),
			'jwtInPlainCallsEnabled' => static::isPlainCallsUseNewScheme(),
		], self::getAdditionalMobileOptions());
	}

	// todo should be moved to callmobile along with the rest of the parameters
	protected static function getAdditionalMobileOptions(): array
	{
		Loader::includeModule('im');

		$userId = (int)CurrentUser::get()->getId();
		$usersData = Im\Call\Util::getUsers([$userId]);

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
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: '';
		if ($region === 'cn')
		{
			return false;
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			// box
			return in_array($region, Library::REGION_CIS, true);
		}

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

		return 'N';
	}

	public static function getBalancerUrl(): string
	{
		return (new BalancerClient())->getServiceUrl();
	}

	//region JWT

	/**
	 * @deprecated
	 */
	public static function registerPortalKey(): bool
	{
		return JwtCall::registerPortal()->isSuccess();
	}

	/**
	 * @deprecated
	 */
	public static function registerPortalKeyAgent(int $retryCount = 1): string
	{
		return JwtCall::registerPortalAgent();
	}

	public static function isNewCallsEnabled(): bool
	{
		$defaultValue = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');

		return
			(bool)Option::get('call', 'call_v2_enabled', $defaultValue)
			&& (self::getPortalId() > 0)
		;
	}

	public static function getPrivateKey(): string
	{
		$privateKey = Option::get('call', 'call_portal_key');

		$cryptoOptions = Configuration::getValue('crypto');
		if (!empty($cryptoOptions['crypto_key']))
		{
			try
			{
				$cipher = new Cipher();
				$privateKey = $cipher->decrypt(base64_decode($privateKey), $cryptoOptions['crypto_key']);
			}
			catch (SecurityException $exception)
			{
			}
		}

		return $privateKey;
	}

	public static function getPortalId(): int
	{
		return (int)Option::get('call', 'call_portal_id', 0);
	}

	public static function isPlainCallsUseNewScheme(): bool
	{
		return (bool)Option::get('call', 'plain_calls_use_new_scheme', false);
	}

	/**
	 * @deprecated
	 */
	public static function updateCallV2Availability(bool $isJwtEnabled, bool $isPlainUseJwt, string $callBalancerUrl = '', string $callServerUrl = ''): void
	{
		JwtCall::updateCallV2Availability($isJwtEnabled, $isPlainUseJwt, $callBalancerUrl, $callServerUrl);
	}

	/**
	 * Disable camera of new joined users feature is enabled.
	 * @return bool
	 */
	public static function isDisableCameraNewJoinedUsersFeatureEnabled(): bool
	{
		if (Option::get('call', 'call_disable_camera_new_joined_users_enabled', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_disable_camera_new_joined_users_enabled', false);
	}
	
	/**
	 * Enable/disable logs to Kibana.
	 * @return bool
	 */
	public static function isKibanaLogsEnabled(): bool
	{
		if (Option::get('call', 'call_kibana_logs_enabled', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_kibana_logs_enabled', false);
	}

	/**
	 * Disable camera of new joined users feature is enabled.
	 * @return int
	 */
	public static function countDisableCameraNewJoinedUsersFeature(): int
	{
		return Option::get('call', 'call_disable_camera_new_joined_users_count', 4);
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

	/**
	 * New mobile grid is enabled.
	 * @return bool
	 */
	public static function isNewMobileGridEnabled(): bool
	{
		if (Option::get('call', 'call_new_mobile_grid', false))
		{
			return true;
		}

		return (bool)\CUserOptions::GetOption('call', 'call_new_mobile_grid', false);
	}
}
