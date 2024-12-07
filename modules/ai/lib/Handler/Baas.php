<?php declare(strict_types=1);

namespace Bitrix\AI\Handler;

use Bitrix\AI\Limiter\Repository\BaasPackageRepository;
use Bitrix\Main\Event;
use Bitrix\AI\Integration\Baas\BaasTokenService;
use Bitrix\Main\Type\Date;

class Baas
{
	/**
	 * Added info package in db
	 */
	public static function onPackagePurchased(Event $event): void
	{
		if ($event->getParameter('serviceCode') !== BaasTokenService::SERVICE_CODE)
		{
			return;
		}

		$startDate = $event->getParameter('startDate');
		$expiredDate = $event->getParameter('expiredDate');
		if (!($expiredDate instanceof Date) || !($startDate instanceof Date))
		{
			return;
		}

		static::getBaasPackageRepository()
			->addPackage($startDate, $expiredDate)
		;
	}

	protected static function getBaasPackageRepository(): BaasPackageRepository
	{
		return new BaasPackageRepository();
	}
}
