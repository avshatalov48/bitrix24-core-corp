<?php

namespace Bitrix\BIConnector\Superset\Grid\Column\Provider;

use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

class UnusedElementsColumnProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('TITLE')
				->setEditable(true)
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_TITLE'))
				->setAlign('left')
				->setDefault(true)
				->setSort(null)
		;

		$result[] =
			$this->createColumn('TYPE')
				->setEditable(true)
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_TYPE'))
				->setAlign('left')
				->setDefault(true)
				->setSort(null)
		;

		$result[] =
			$this->createColumn('DESCRIPTION')
				->setEditable(false)
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_DESCRIPTION'))
				->setAlign('left')
				->setDefault(true)
				->setSort(null)
		;

		$result[] =
			$this->createColumn('OWNERS')
				->setType(Type::TEXT)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_OWNERS'))
				->setAlign('left')
				->setDefault(true)
				->setSort(null)
		;

		$result[] =
			$this->createColumn('EXTERNAL_ID')
				->setEditable(true)
				->setType(Type::INT)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_ID'))
				->setAlign('left')
				->setDefault(false)
				->setSort(null)
		;

		$result[] =
			$this->createColumn('DATE_UPDATE')
				->setEditable(false)
				->setType(Type::DATE)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_DATE_UPDATE'))
				->setAlign('left')
				->setDefault(true)
				->setSort('DATE_UPDATE')
		;

		$result[] =
			$this->createColumn('DATE_CREATE')
				->setEditable(false)
				->setType(Type::DATE)
				->setName(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_COLUMN_NAME_DATE_CREATE'))
				->setAlign('left')
				->setDefault(true)
				->setSort('DATE_CREATE')
		;

		return $result;
	}

	public static function getColumnTitles(): array
	{
		$columns = (new UnusedElementsColumnProvider)->prepareColumns();
		$result = [];
		foreach ($columns as $column)
		{
			$result[] = $column->getId();
		}

		return $result;
	}
}