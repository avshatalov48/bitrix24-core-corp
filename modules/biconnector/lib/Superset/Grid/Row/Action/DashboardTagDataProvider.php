<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Grid\Row\Action\DataProvider;

class DashboardTagDataProvider extends DataProvider
{
	public function prepareActions(): array
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_TAG_MODIFY))
		{
			return [];
		}

		return [
			new EditTagAction(),
			new DeleteTagAction(),
		];
	}
}
