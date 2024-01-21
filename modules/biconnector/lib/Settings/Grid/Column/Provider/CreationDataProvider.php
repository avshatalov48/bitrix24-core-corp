<?php

namespace Bitrix\BiConnector\Settings\Grid\Column\Provider;

use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;

class CreationDataProvider extends Grid\Column\DataProvider
{
	public function prepareColumns(): array
	{
		return [
			$this->getIDColumn(),
			$this->getCreatedByColumn(),
			$this->getDateCreateColumn(),
			$this->getDateEditColumn(),
		];
	}

	protected function getIDColumn(): Grid\Column\Column
	{
		return $this->createColumn('ID')
			->setType(Grid\Column\Type::INT)
			->setDefault(false)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_CREATION_ID'))
			->setEditable(false)
			->setSort('ID');
	}

	protected function getCreatedByColumn(): Grid\Column\Column
	{
		return $this->createColumn('CREATED_BY')
			->setType(Grid\Column\Type::HTML)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_CREATION_CREATED_BY'))
			->setEditable(false)
			->setSort('CREATED_BY');
	}

	protected function getDateCreateColumn(): Grid\Column\Column
	{
		return $this->createColumn('DATE_CREATE')
			->setType(Grid\Column\Type::DATE)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_CREATION_DATE_CREATE'))
			->setEditable(false)
			->setSort('DATE_CREATE');
	}

	protected function getDateEditColumn(): Grid\Column\Column
	{
		return $this->createColumn('TIMESTAMP_X')
			->setType(Grid\Column\Type::DATE)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_CREATION_DATE_EDIT'))
			->setEditable(false)
			->setSort('TIMESTAMP_X');
	}
}
