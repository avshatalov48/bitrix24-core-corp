<?php

declare(strict_types=1);

namespace Bitrix\Baas\Public\Provider;

use Bitrix\Baas\Internal\Service\BaasService;
use Bitrix\Baas\Internal\Service\BillingSynchronizationService;

trait DailyBillingSyncTrait
{
	protected function checkAndSyncOncePerDay(): void
	{
		if (BaasService::getInstance()->isAvailable())
		{
			BillingSynchronizationService::getInstance()->syncIfNeeded();
		}
	}
}
