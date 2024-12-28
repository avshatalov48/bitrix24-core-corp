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
				->setEditable(true)
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
				->setDefault(false)
		;

		$result[] =
			$this->createColumn('OWNER_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_OWNER'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('TAGS')
				->setType(Type::TAGS)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_TAGS'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('SCOPE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_SCOPE'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('URL_PARAMS')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_URL_PARAMS'))
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

		$result[] =
			$this->createColumn('DATE_MODIFY')
				 ->setType(Type::DATE)
				 ->setName(Loc::getMessage('BICONNECTOR_SUPERSET_GRID_COLUMN_TITLE_DATE_MODIFY'))
				 ->setAlign('left')
				 ->setDefault(false)
				 ->setSort('DATE_MODIFY')
		;

		return $result;
	}
}
