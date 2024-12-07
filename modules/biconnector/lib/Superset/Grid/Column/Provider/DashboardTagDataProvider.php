<?php

namespace Bitrix\BIConnector\Superset\Grid\Column\Provider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

class DashboardTagDataProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('TITLE')
				->setEditable(true)
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_TAG_GRID_COLUMN_TITLE_NAME'))
				->setAlign('left')
				->setDefault(true)
				->setSort('TITLE')
		;

		$result[] =
			$this->createColumn('DASHBOARD_COUNT')
				->setEditable(true)
				->setType(Type::INT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_TAG_GRID_COLUMN_DASHBOARD_COUNT_NAME'))
				->setAlign('left')
				->setDefault(true)
				->setWidth(300)
		;

		return $result;
	}
}
