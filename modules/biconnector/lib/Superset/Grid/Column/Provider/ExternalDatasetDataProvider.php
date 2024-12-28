<?php

namespace Bitrix\BIConnector\Superset\Grid\Column\Provider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

class ExternalDatasetDataProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('NAME')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_NAME'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('TYPE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_TYPE_MSGVER_1'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('SOURCE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_SOURCE'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('CREATED_BY_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_CREATED_BY'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('DATE_CREATE')
				->setType(Type::DATE)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_DATE_CREATE'))
				->setAlign('left')
				->setDefault(true)
				->setSort('DATE_CREATE')
		;

		$result[] =
			$this->createColumn('UPDATED_BY_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_UPDATED_BY'))
				->setAlign('left')
				->setDefault(false)
		;

		$result[] =
			$this->createColumn('DATE_UPDATE')
				->setType(Type::DATE)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_DATE_UPDATE'))
				->setAlign('left')
				->setDefault(true)
				->setSort('DATE_UPDATE')
		;

		$result[] =
			$this->createColumn('DESCRIPTION')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_DESCRIPTION'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_COLUMN_TITLE_ID'))
				->setAlign('left')
				->setDefault(false)
				->setWidth(80)
				->setSort('ID')
		;

		return $result;
	}
}
