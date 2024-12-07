<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Web\Uri;

final class SupersetDashboard extends EO_SupersetDashboard
{
	public function getDetailUrl(array $urlParams = []): Uri
	{
		return
			(new Uri("/bi/dashboard/detail/{$this->getId()}/"))
				->addParams($urlParams)
			;
	}

	/**
	 * @return array
	 */
	public static function getActiveDashboardStatuses(): array
	{
		return [
			SupersetDashboardTable::DASHBOARD_STATUS_READY,
			SupersetDashboardTable::DASHBOARD_STATUS_DRAFT,
		];
	}
}
