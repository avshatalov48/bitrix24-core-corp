<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\Main;
use Bitrix\BIConnector;
use Bitrix\Rest;

class Agent
{
	public static function sendRestStatistic(): string
	{
		if (
			Main\Loader::includeModule('rest')
			&& is_callable(['\Bitrix\Rest\UsageStatTable', 'logBISuperset'])
			&& BIConnector\Integration\Superset\SupersetInitializer::isSupersetActive()
		)
		{
			$dashboardIterator = BIConnector\Integration\Superset\Model\SupersetDashboardTable::getList([
				'select' => [
					'TYPE',
					'REST_APP_CLIENT_ID' => 'APP.CLIENT_ID'
				],
			]);
			while ($dashboard = $dashboardIterator->fetch())
			{
				if ($dashboard['REST_APP_CLIENT_ID'])
				{
					Rest\UsageStatTable::logBISuperset($dashboard['REST_APP_CLIENT_ID'], $dashboard['TYPE']);
				}
			}

			Rest\UsageStatTable::finalize();
		}

		return __CLASS__ . '::' . __FUNCTION__ . '();';
	}
}