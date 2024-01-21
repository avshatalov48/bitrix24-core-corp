<?php

namespace Bitrix\BIConnector\Superset\Grid\Column\Provider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

class DashboardDataProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('TITLE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_NAME'))
				->setAlign('left')
				->setDefault(true)
				->setSort('TITLE')
		;

		$result[] =
			$this->createColumn('STATUS')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_STATUS'))
				->setAlign('left')
				->setDefault(true)
				->setWidth(150)
		;

		$result[] =
			$this->createColumn('CREATED_BY_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_AUTHOR'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('EDIT_URL')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_ACTIONS'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('SOURCE_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_BASED_ON'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('FILTER_PERIOD')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_DATE_PERIOD'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_ID'))
				->setAlign('left')
				->setDefault(true)
				->setWidth(80)
				->setSort('ID')
		;

		$result[] =
			$this->createColumn('DATE_CREATE')
				->setType(Type::DATE)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_DATE_CREATE'))
				->setAlign('left')
				->setDefault(false)
				->setSort('DATE_CREATE')
		;

		return $result;
	}
}
