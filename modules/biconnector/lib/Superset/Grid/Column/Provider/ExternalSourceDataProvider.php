<?php

namespace Bitrix\BIConnector\Superset\Grid\Column\Provider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

class ExternalSourceDataProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('TITLE')
				->setEditable(true)
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_TITLE_MSGVER_1'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('TYPE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_TYPE'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('ACTIVE')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_ACTIVE'))
				->setAlign('left')
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('DATE_CREATE')
				->setType(Type::DATE)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_DATE_CREATE'))
				->setAlign('left')
				->setDefault(true)
				->setSort('DATE_CREATE')
		;

		$result[] =
			$this->createColumn('CREATED_BY_ID')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_CREATED_BY'))
				->setAlign('left')
				->setDefault(false)
		;

		// $result[] =
		// 	$this->createColumn('DESCRIPTION')
		// 		->setEditable(true)
		// 		->setType(Type::TEXT)
		// 		->setName(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COLUMN_TITLE_DESCRIPTION'))
		// 		->setAlign('left')
		// 		->setDefault(false)
		// ;

		return $result;
	}
}