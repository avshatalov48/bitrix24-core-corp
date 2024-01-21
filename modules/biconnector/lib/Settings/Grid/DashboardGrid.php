<?php

namespace Bitrix\BiConnector\Settings\Grid;

use Bitrix\BiConnector\Settings\Grid\Row\Action\DashboardDataProvider;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\DashboardRowAssembler;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Grid;
use Bitrix\BiConnector\Settings\Grid\Column\Provider;
use Bitrix\Main\Grid\Column;
use Bitrix\Main\Grid\Row\Rows;
use Bitrix\BiConnector;

/**
 * @method DashboardSettings getSettings()
 */
class DashboardGrid extends Grid
{
	protected function createColumns(): Column\Columns
	{
		return new Column\Columns(
			new Provider\DashboardDataProvider(),
			new Provider\CreationDataProvider(),
		);
	}

	protected function createRows(): Rows
	{
		return new Rows(
			new DashboardRowAssembler([
				'CREATED_BY',
				'DATE_LAST_VIEW',
				'LAST_VIEW_BY',
				'DATE_CREATE',
				'TIMESTAMP_X',
				'NAME',
				'URL',
			]),
			new DashboardDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new BiConnector\Settings\Filter\Provider\DashboardDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}
}
