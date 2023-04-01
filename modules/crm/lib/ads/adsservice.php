<?php

namespace Bitrix\Crm\Ads;

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use \Bitrix\Seo\Retargeting\Service;

/**
 * Class AdsService.
 * @package Bitrix\Crm\Ads
 */
abstract class AdsService
{
	/** @var array $errors Errors. */
	protected static $errors = array();

	/** @var array $logs Log messages. */
	protected static $logs = array();

	/** @var bool $isLogsEnabled Is log enabled. */
	protected static $isLogsEnabled = false;

	/**
	 * Check use parameter.
	 *
	 * @return bool
	 */
	protected static function checkUseParameter($name)
	{
		$request = Context::getCurrent()->getRequest();
		if ($request->get($name))
		{
			$_SESSION[$name] = $request->get($name) == 'Y';
		}

		if (!isset($_SESSION[$name]) || !$_SESSION[$name])
		{
			return false;
		}

		return true;
	}

	/**
	 * Return true if it can use.
	 *
	 * @return bool
	 */
	public static function canUse()
	{
		return Loader::includeModule('seo') && Loader::includeModule('socialservices');
	}

	/**
	 * Enable logs.
	 *
	 * @return void
	 */
	public static function enableLogs()
	{
		self::$logs = array();
		self::$isLogsEnabled = true;
	}

	protected static function log($message)
	{
		if (self::$isLogsEnabled)
		{
			self::$logs[] = $message;
		}
	}

	/**
	 * Get logs.
	 *
	 * @return array
	 */
	public static function getLogs()
	{
		return self::$logs;
	}

	/**
	 * Get logs.
	 *
	 * @return array
	 */
	public static function getErrors()
	{
		return self::$errors;
	}

	/**
	 * Reset errors.
	 *
	 * @return void
	 */
	public static function resetErrors()
	{
		self::$errors = array();
	}

	/**
	 * Return true if it has errors.
	 *
	 * @return bool
	 */
	public static function hasErrors()
	{
		return count(self::$errors) > 0;
	}

	/**
	 * Remove auth.
	 *
	 * @param string $type Type.
	 */
	public static function removeAuth($type)
	{
		static::getService()->getAuthAdapter($type)->removeAuth();
	}

	/**
	 * Get service types.
	 *
	 * @return array
	 */
	public static function getServiceTypes()
	{
		if (!static::canUse())
		{
			return array();
		}

		$service = static::getService();
		$types = $service->getTypes();
		if (!Loader::includeModule('bitrix24') || in_array(\CBitrix24::getPortalZone(), ['ru', 'kz', 'by']))
		{
			return $types;
		}

		$result = [];
		foreach ($types as $type)
		{
			if ($type === $service::TYPE_VKONTAKTE)
			{
				continue;
			}

			$result[] = $type;
		}

		return $result;
	}

	protected static function getServiceProviders(array $types = null)
	{
		$typeList = static::getServiceTypes();

		$providers = array();
		foreach ($typeList as $type)
		{
			if ($types && !in_array($type, $types))
			{
				continue;
			}

			$authAdapter = static::getService()->getAuthAdapter($type);
			$account = static::getService()->getAccount($type);

			$providers[$type] = array(
				'ENGINE_CODE' => static::getService()::getEngineCode($type),
				'TYPE' => $type,
				'HAS_AUTH' => $authAdapter->hasAuth(),
				'AUTH_URL' => $authAdapter->getAuthUrl(),
				'PROFILE' => $account->getProfileCached(),
				'HAS_PAGES' => $account->hasPageAccount(),
			);

			// check if no profile, then may be auth was removed in service
			if ($providers[$type]['HAS_AUTH'] && empty($providers[$type]['PROFILE']))
			{
				static::removeAuth($type);
				$providers[$type]['HAS_AUTH'] = false;
			}
		}

		return $providers;
	}

	/**
	 * Get providers.
	 *
	 * @param array|null $types Types.
	 * @return array
	 */
	public static function getProviders(array $types = null)
	{
		if (!static::canUse())
		{
			return array();
		}

		return static::getServiceProviders($types);
	}

	/**
	 * Get service.
	 *
	 * @return Service
	 */
	public static function getService()
	{
		return Service::getInstance();
	}

	/**
	 * Get accounts.
	 *
	 * @param string $type Type.
	 * @return array
	 */
	public static function getAccounts($type)
	{
		if (!static::canUse())
		{
			return [];
		}

		$account = static::getService()->getAccount($type);
		$accountsResponse = $account->getList();

		if (!$accountsResponse->isSuccess())
		{
			self::$errors = $accountsResponse->getErrorMessages();

			return [];
		}

		for($result = [];$accountData = $accountsResponse->fetch();)
		{
			$accountData = $account::normalizeListRow($accountData);
			if ($accountData['ID'])
			{
				$result[] = array(
					'id' => $accountData['ID'],
					'name' => $accountData['NAME'] ? : $accountData['ID']
				);
			}
		}

		return $result;
	}
}
