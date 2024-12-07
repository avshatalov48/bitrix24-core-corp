<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Superset\Grid\Column\Provider\DashboardTagDataProvider;
use Bitrix\BIConnector\Superset\Grid\Row\Action;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\DashboardTagRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method DashboardSettings getSettings()
 */
final class DashboardTagGrid extends Grid
{
	protected function createColumns(): Columns
	{
		return new Columns(
			new DashboardTagDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new DashboardTagRowAssembler(
			[
				'TITLE',
				'DASHBOARD_COUNT',
			]
		);
		
		return new Rows(
			$rowAssembler,
			new Action\DashboardTagDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\DashboardTagDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}
}
