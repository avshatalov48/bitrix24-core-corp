<?php

namespace Bitrix\BiConnector\Settings\Grid\Column\Provider;

use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;

class DashboardDataProvider extends Grid\Column\DataProvider
{
	public function prepareColumns(): array
	{
		return [
			$this->getNameColumn(),
			$this->getUrlColumn(),
			$this->getLastViewByColumn(),
			$this->getDateLastViewByColumn(),
		];
	}

	protected function getNameColumn(): Grid\Column\Column
	{
		return $this->createColumn('NAME')
			->setType(Grid\Column\Type::TEXT)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_DASHBOARD_NAME'))
			->setEditable(false)
			->setSort('ID');
	}

	protected function getUrlColumn(): Grid\Column\Column
	{
		return $this->createColumn('URL')
			->setType(Grid\Column\Type::HTML)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_DASHBOARD_URL'))
			->setEditable(false)
			->setSort('URL');
	}

	protected function getLastViewByColumn(): Grid\Column\Column
	{
		return $this->createColumn('LAST_VIEW_BY')
			->setType(Grid\Column\Type::HTML)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_DASHBOARD_LAST_VIEW_BY'))
			->setEditable(false)
			->setSort('LAST_VIEW_BY');
	}

	protected function getDateLastViewByColumn(): Grid\Column\Column
	{
		return $this->createColumn('DATE_LAST_VIEW')
			->setType(Grid\Column\Type::DATE)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_DASHBOARD_DATE_LAST_VIEW'))
			->setEditable(false)
			->setSort('DATE_LAST_VIEW');
	}
}
