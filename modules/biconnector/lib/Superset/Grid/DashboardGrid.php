<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\DashboardRowAssembler;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

final class DashboardGrid extends Grid
{
	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\DashboardDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		return new Rows(
			new DashboardRowAssembler([
				'TITLE',
				'STATUS',
				'CREATED_BY_ID',
				'DATE_CREATE',
				'SOURCE_ID',
				'EDIT_URL',
				'FILTER_PERIOD',
				'ID',
			]),
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\DashboardDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\DashboardDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}
}
