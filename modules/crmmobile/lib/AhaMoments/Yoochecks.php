<?php

namespace Bitrix\CrmMobile\AhaMoments;

use Bitrix\Main\Loader;
use Bitrix\Sale\Cashbox\CashboxYooKassa;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Seo\Checkout\Service;
use CIntranetUtils;

class Yoochecks extends Base
{
	protected const OPTION_NAME = 'yoochecks';

	public function canShow(): bool
	{
		if (!parent::canShow())
		{
			return false;
		}

		$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : '';
		$portalZone = Loader::includeModule('intranet') ? CIntranetUtils::getPortalZone() : '';
		if (Loader::includeModule('bitrix24'))
		{
			if ($licensePrefix !== 'ru')
			{
				return false;
			}
		}
		elseif (Loader::includeModule('intranet') && $portalZone !== 'ru')
		{
			return false;
		}

		if (
			!Loader::includeModule('sale')
			|| !Loader::includeModule('salescenter')
			|| !Loader::includeModule('seo')
		)
		{
			return false;
		}

		$cashboxList = CashboxTable::getList([
			'select' => ['ID'],
			'filter' => [
				'ACTIVE' => 'Y',
				'=HANDLER' => '\\' . CashboxYooKassa::class,
			],
			'limit' => 1,
		]);

		if ($cashboxList->fetch())
		{
			return false;
		}

		if (!SaleManager::getInstance()->isFullAccess(true))
		{
			return false;
		}

		$authAdapter = Service::getAuthAdapter(Service::TYPE_YOOKASSA);
		if ($authAdapter->hasAuth())
		{
			return false;
		}

		return true;
	}
}
