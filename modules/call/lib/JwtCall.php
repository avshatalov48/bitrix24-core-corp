<?php

namespace Bitrix\Call;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\Web\JWT;
use Bitrix\Im\Call\Call;
use Bitrix\Main\Service\MicroService\Client;

class JwtCall
{
	/** @var array<int, string> */
	private static array $userTokens = [];

	/** @var array<int, string> */
	private static array $callTokens = [];

	/** @var array<int, string> */
	private static array $userRoles = [];

	/**
	 * Generates a secret key for call JWT
	 */
	public static function registerPortal(): Result
	{
		$result = new Result();

		$privateKey = base64_encode(Random::getBytes(32));

		$registrationResult = (new ControllerClient())->registerCallKey($privateKey);
		if ($registrationResult->isSuccess())
		{
			$data = $registrationResult->getData();
			if (isset($data['PORTAL_ID']))
			{
				try
				{
					$cryptoOptions = Configuration::getValue('crypto');
					if (!empty($cryptoOptions['crypto_key']))
					{
						$cipher = new Cipher();
						$privateKey = base64_encode($cipher->encrypt($privateKey, $cryptoOptions['crypto_key']));
					}
				}
				catch (SecurityException $exception)
				{
				}

				Option::set('call', 'call_portal_key', $privateKey);
				Option::set('call', 'call_portal_id', $data['PORTAL_ID']);

				return $result;
			}
			else
			{
				$result->addError(new Error(Error::PORTAL_REGISTER_ERROR, 'Failed register portal. Empty portal ID'));
			}
		}
		else
		{
			$result->addErrors($registrationResult->getErrors());
		}

		return $result;
	}

	public static function registerPortalAgent(int $retryCount = 1): string
	{
		$portalId = Settings::getPortalId();
		if (!empty($portalId))
		{
			$registrationDataResult = (new ControllerClient())->getRegistrationData();
			if ($registrationDataResult->isSuccess())
			{
				$data = $registrationDataResult->getData();
				if (isset($data['PORTAL_ID']) && (int)$data['PORTAL_ID'] == $portalId)
				{
					Option::set('call', 'call_v2_enabled', true);

					Signaling::sendClearCallTokens();
					NotifyService::getInstance()->addAdminNotify(Loc::getMessage('CALL_REGISTRATION_ADMIN_NOTIFY'));

					return '';
				}
			}
		}

		$checkPublicUrlResult = self::checkPublicUrl();
		if (!$checkPublicUrlResult->isSuccess())
		{
			NotifyService::getInstance()->addAdminNotifyError(
				Loc::getMessage('CALL_REGISTRATION_ADMIN_NOTIFY_ERROR', ['#LINK#' => '/bitrix/admin/settings.php?mid=call&mid_menu=1&lang='.LANGUAGE_ID])
			);
		}
		else
		{
			$registerPortalResult = self::registerPortal();
			if (!$registerPortalResult->isSuccess())
			{
				NotifyService::getInstance()->addAdminNotifyError(
					Loc::getMessage('CALL_REGISTRATION_ADMIN_NOTIFY_ERROR', ['#LINK#' => '/bitrix/admin/settings.php?mid=call&mid_menu=1&lang='.LANGUAGE_ID])
				);
			}
			else
			{
				Option::set('call', 'call_v2_enabled', true);
				Signaling::sendClearCallTokens();
				NotifyService::getInstance()->addAdminNotify(Loc::getMessage('CALL_REGISTRATION_ADMIN_NOTIFY'));

				return '';
			}
		}

		$retryCount ++;
		if ($retryCount > 100)
		{
			return '';
		}

		return __METHOD__ . "({$retryCount});";
	}

	/**
	 * Unregister portal JWT key
	 */
	public static function unregisterPortal(): Result
	{
		$result = new Result();

		Option::delete('call', ['name' => 'call_portal_key']);
		Option::delete('call', ['name' => 'call_portal_id']);

		$registrationResult = (new ControllerClient())->unregisterCallKey();
		if (!$registrationResult->isSuccess())
		{
			$result->addErrors($registrationResult->getErrors());
		}

		return $result;
	}

	public static function updateCallV2Availability(bool $isJwtEnabled, bool $isPlainUseJwt, string $callBalancerUrl = '', string $callServerUrl = ''): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		Option::set('call', 'call_v2_enabled', $isJwtEnabled);
		Option::set('call', 'plain_calls_use_new_scheme', $isPlainUseJwt);

		if ($callBalancerUrl)
		{
			Option::set('call', 'call_balancer_url', $callBalancerUrl);
		}

		if ($callServerUrl)
		{
			Option::set('im', 'call_server_url', $callServerUrl);
		}

		Signaling::sendChangedCallV2Enable($isJwtEnabled, $isPlainUseJwt, $callBalancerUrl);
	}

	/**
	 * Checks availability of the external public url.
	 * @param string|null $publicUrl Portal public url.
	 * @return Result
	 */
	public static function checkPublicUrl(?string $publicUrl = null): Result
	{
		$result = new Result();

		$publicUrl = $publicUrl ?? Library::getPortalPublicUrl();
		if (empty($publicUrl))
		{
			return $result->addError(new Error(Error::PUBLIC_URL_EMPTY));
		}

		if (
			!($parsedUrl = \parse_url($publicUrl))
			|| empty($parsedUrl['host'])
			|| strpos($parsedUrl['host'], '.') === false
			|| !in_array($parsedUrl['scheme'], ['http', 'https'])
		)
		{
			return $result->addError(new Error(Error::PUBLIC_URL_MALFORMED));
		}

		// check for local address
		$host = $parsedUrl['host'];
		if (
			strtolower($host) == 'localhost'
			|| $host == '0.0.0.0'
			||
			(
				preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)
				&& preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
			)
		)
		{
			return $result->addError(new Error(Error::PUBLIC_URL_LOCALHOST, ['HOST' => $host]));
		}

		$error = (new \Bitrix\Main\Web\Uri($publicUrl))->convertToPunycode();
		if ($error instanceof \Bitrix\Main\Error)
		{
			return $result->addError(new Error(
				Error::PUBLIC_URL_CONVERTING_PUNYCODE,
				['HOST' => $host, 'ERROR' => $error->getMessage()]
			));
		}

		$port = '';
		if (
			isset($parsedUrl['port'])
			&& (int)$parsedUrl['port'] > 0
		)
		{
			$port = ':'.(int)$parsedUrl['port'];
		}

		$portalUrl = $parsedUrl['scheme'].'://'.$parsedUrl['host']. $port;

		$checkResult = (new ControllerClient())->checkPublicUrl($portalUrl);
		if (!$checkResult->isSuccess())
		{
			$errorCode = mb_strtoupper($checkResult->getError()->getCode());
			$error = new Error($errorCode, ['ERROR' => $errorCode]);

			if (empty($error->getMessage()))
			{
				$error = new Error($errorCode, Loc::getMessage('ERROR_PUBLIC_URL_FAIL', ['#ERROR#' => $errorCode]));
			}

			return $result->addError($error);
		}

		return $result;
	}

	/**
	 * Generates a user JWT for a call
	 * @param int $userId
	 * @return string
	 */
	public static function getUserJwt(int $userId): string
	{
		if (!isset(self::$userTokens[$userId]))
		{
			$user = \Bitrix\Im\User::getInstance($userId);
			$userData = [
				'userId' => $user->getId(),
				'userName' => $user->getFullName(false),
				'avatar' => $user->getAvatar(),
			];

			self::$userTokens[$userId] = JWT::encode($userData, Settings::getPrivateKey());
		}

		return self::$userTokens[$userId];
	}

	/**
	 * Return call JWT
	 *
	 * @param int $chatId Chat info for token
	 * @param array|null $additionalData Additional info for call token
	 *
	 * @return string
	 */
	public static function getCallToken(int $chatId, array|null $additionalData = null): string
	{
		if (!isset(self::$callTokens[$chatId]))
		{
			self::$callTokens[$chatId] = self::generateCallJwt($chatId, self::getTokenVersion($chatId), self::getUserRoles($chatId), $additionalData);
		}

		return self::$callTokens[$chatId];
	}

	/**
	 * Return call JWT list
	 *
	 * @param array<int> $chatIdList List of chat ids for get token
	 * @param array|null $additionalData Additional info for call token
	 *
	 * @return array<string>
	 */
	public static function getCallTokenList(array $chatIdList, array|null $additionalData = null): array
	{
		$chatEntityList = CallChatEntity::findAll($chatIdList);
		foreach ($chatIdList as $chatId)
		{
			if (isset(self::$callTokens[$chatId]))
			{
				continue;
			}
			$tokenVersion = 1;
			if (isset($chatEntityList[$chatId]))
			{
				$tokenVersion = $chatEntityList[$chatId];
			}

			$additionalFields = [];
			if (isset($additionalData[$chatId]))
			{
				$additionalFields = $additionalData[$chatId];
			}

			self::$callTokens[$chatId] = self::generateCallJwt($chatId, $tokenVersion, self::getUserRoles($chatId), $additionalFields);
		}

		return array_intersect_key(self::$callTokens, array_flip($chatIdList));
	}

	/**
	 * Update call JWT version
	 *
	 * @param int $chatId Chat id for token update
	 * @return string
	 */
	public static function updateCallToken(int $chatId): string
	{
		$chatEntity = CallChatEntity::updateVersion($chatId);

		$newTokenVersion = $chatEntity->getCallTokenVersion();
		(new BalancerClient())->updateTokenVersion($chatId, $newTokenVersion);

		self::$callTokens[$chatId] = self::generateCallJwt($chatId, $newTokenVersion, self::getUserRoles($chatId));

		return self::$callTokens[$chatId];
	}

	/**
	 * Get version for call token
	 * @param int $chatId Chat id for token update
	 * @return int|null
	 */
	public static function getTokenVersion(int $chatId): ?int
	{
		if (empty($chatId))
		{
			return null;
		}

		$tokenVersion = 1;
		$chatEntity = CallChatEntity::find($chatId);
		if ($chatEntity)
		{
			$tokenVersion = $chatEntity->getCallTokenVersion();
		}

		return $tokenVersion;
	}

	/**
	 * Generates call JWT
	 *
	 * @param int $chatId
	 * @param int $tokenVersion
	 * @param array $userRoles
	 * @param array|null $additionalData Additional info for call token
	 *
	 * @return string
	 */
	private static function generateCallJwt(int $chatId, int $tokenVersion, array $userRoles, array|null $additionalData = null): string
	{
		$callToken = [
			'portalId' => Settings::getPortalId(),
			'chatId' => $chatId,
			'tokenVersion' => $tokenVersion,
			'usersLimit' => Call::getMaxCallServerParticipants(),
			'portalType' => Client::getPortalType(),
			'portalUrl' => Client::getServerName(),
			'controllerUrl' => (new ControllerClient())->getServiceUrl(),
			'roomType' => 1,
		];
		if (!empty($additionalData))
		{
			$callToken['additionalData'] = $additionalData;
		}
		if (!empty($userRoles))
		{
			$callToken['userRoles'] = $userRoles;
		}

		return JWT::encode($callToken, Settings::getPrivateKey());
	}

	private static function getUserRoles(int $chatId): array
	{
		if (!isset(self::$userRoles[$chatId]))
		{
			$roles = [];
			\Bitrix\Main\Loader::includeModule('im');

			$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
			if ($chat->getId() == $chatId)
			{
				if ($chat->getAuthorId())
				{
					$roles['admin'] = $chat->getAuthorId();
				}

				$manages = $chat->getManagerList();
				if (!empty($manages))
				{
					$roles['manager'] = $manages;
				}
			}

			self::$userRoles[$chatId] = $roles;
		}

		return self::$userRoles[$chatId] ?? [];
	}
}
