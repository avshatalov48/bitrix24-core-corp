<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Rest\Application;
use Bitrix\Rest\Marketplace;
use Bitrix\Rest\AppTable;

class Rest
{
	/**
	 * Applications we need to install for proper usage in some cases.
	 */
	private const MARKET_KIT = [
		'gpt_ru' => ['itsolutionru.gptconnector', 'asmo.ai'],
	];

	/**
	 * Returns REST Application code by id (you can take this during REST execution).
	 *
	 * @param string|null $applicationId REST Application id.
	 * @return string|null
	 */
	public static function getApplicationCode(?string $applicationId): ?string
	{
		if (Loader::includeModule('rest'))
		{
			return AppTable::getByClientId($applicationId)['CODE'] ?? null;
		}

		return null;
	}

	/**
	 * Returns REST Application Client id by Application code.
	 *
	 * @param string $applicationCode REST Application code.
	 * @return string|null
	 */
	public static function getApplicationClientId(string $applicationCode): ?string
	{
		if (!Loader::includeModule('rest'))
		{
			return null;
		}

		$res = AppTable::query()
			->setSelect(['CLIENT_ID'])
			->where('CODE', $applicationCode)
			->setLimit(1)
			->fetch()
		;

		return $res['CLIENT_ID'] ?? null;
	}

	/**
	 * Returns true if REST application is expired.
	 *
	 * @param string $applicationCode Application code.
	 * @return bool
	 */
	public static function isApplicationExpired(string $applicationCode): bool
	{
		if (Loader::includeModule('rest'))
		{
			$app = AppTable::query()
				->setSelect(['*'])
				->where('CODE', $applicationCode)
				->setLimit(1)
				->fetch()
			;
			if ($app)
			{
				$status = AppTable::getAppStatusInfo($app, '');
				if ($status['PAYMENT_ALLOW'] !== 'Y')
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns Application's auth information.
	 *
	 * @param string $applicationCode Application code.
	 * @return array|null
	 */
	public static function getAuthInfo(string $applicationCode): ?array
	{
		$applicationId = self::getApplicationClientId($applicationCode);

		if (!empty($applicationId) && Loader::includeModule('rest'))
		{
			$result = Application::getAuthProvider()->get(
				$applicationId,
				'',
				[],
				User::getCurrentUserId(),
			);
			if (!empty($result['error']))
			{
				return null;
			}
			if (is_array($result))
			{
				return $result;
			}
		}

		return null;
	}

	/**
	 * Returns all Market Kits keys.
	 *
	 * @return array
	 */
	public static function getMarketKits(): array
	{
		if (in_array(Bitrix24::getPortalZone(), ['ru', 'by'], true))
		{
			return array_keys(self::MARKET_KIT);
		}

		return [];
	}

	/**
	 * Checks that Market kit (set of applications) is installed.
	 *
	 * @param string $kitCode Kit code.
	 * @return bool
	 */
	public static function isMarketKitInstalled(string $kitCode): bool
	{
		if (!Loader::includeModule('rest'))
		{
			return false;
		}

		if (empty(self::MARKET_KIT[$kitCode]))
		{
			return false;
		}

		return self::isAppInstalled(self::MARKET_KIT[$kitCode]);
	}

	/**
	 * Install Market kit (set of applications) by kit code.
	 *
	 * @param string $kitCode Kit code.
	 * @param Error|null $error Error instance for getting error if occurred.
	 * @return void
	 */
	public static function installMarketKit(string $kitCode, Error &$error = null): void
	{
		if (!Loader::includeModule('rest'))
		{
			return;
		}

		if (empty(self::MARKET_KIT[$kitCode]))
		{
			return;
		}

		foreach (self::MARKET_KIT[$kitCode] as $code)
		{
			self::installApp($code, $error);
		}
	}

	/**
	 * Installs application by code.
	 *
	 * @param string $code App code.
	 * @return void
	 */
	private static function installApp(string $code, Error &$error = null): void
	{
		if (self::isAppInstalled($code))
		{
			return;
		}

		$result = Marketplace\Application::install($code);
		if (!empty($result['error']))
		{
			$error = new Error($result['errorDescription'] ?? '', $result['error']);
		}
	}

	/**
	 * Checks that app (apps) is installed. Returns false if at least one of app is not installed.
	 *
	 * @param string|array $codes
	 * @return bool
	 */
	private static function isAppInstalled(string|array $codes): bool
	{
		$codes = (array)$codes;

		$row = AppTable::query()
			->addSelect(Query::expr()->count('ID'), 'CNT')
			->whereIn('CODE', $codes)
			->exec()
			->fetch()
		;

		return (int)$row['CNT'] === count($codes);
	}
}
