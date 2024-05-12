<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\DashboardRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method DashboardSettings getSettings()
 */
final class DashboardGrid extends Grid
{

	public function setSupersetAvailability(bool $isSupersetAvailable): void
	{
		$this->getSettings()->setSupersetAvailability($isSupersetAvailable);
	}

	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\DashboardDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new DashboardRowAssembler(
			[
				'TITLE',
				'STATUS',
				'CREATED_BY_ID',
				'DATE_CREATE',
				'DATE_MODIFY',
				'SOURCE_ID',
				'EDIT_URL',
				'FILTER_PERIOD',
				'ID',
			],
			$this->getSettings()
		);

		return new Rows(
			$rowAssembler,
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
