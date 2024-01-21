<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\DataProvider;

class DashboardDataProvider extends DataProvider
{

	public function prepareActions(): array
	{
		return [
			new OpenAction(),
			new EditAction(),
			new CopyAction(),
			new DeleteAction(),
			new SettingPeriodAction(),
			new ExportAction(),
		];
	}
}
