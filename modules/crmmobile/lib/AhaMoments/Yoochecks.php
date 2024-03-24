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
		return false;
	}
}
