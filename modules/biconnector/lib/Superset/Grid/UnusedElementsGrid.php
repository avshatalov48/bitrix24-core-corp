<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Superset\Grid\Column\Provider\UnusedElementsColumnProvider;
use Bitrix\BIConnector\Superset\Grid\Panel\UnusedElementsPanelProvider;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\UnusedElementsRowAssembler;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Pagination\PaginationFactory;
use Bitrix\Main\Grid\Panel\Panel;
use Bitrix\Main\Grid\Row\Rows;
use Bitrix\Main\UI\PageNavigation;

class UnusedElementsGrid extends Grid
{
	protected function createColumns(): Columns
	{
		return new Columns(new UnusedElementsColumnProvider());
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new UnusedElementsRowAssembler(
			UnusedElementsColumnProvider::getColumnTitles(),
		);

		return new Rows(
			$rowAssembler,
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\UnusedElements\UnusedElementsActionProvider(),
		);
	}

	protected function createPanel(): ?Panel
	{
		return new Panel(new UnusedElementsPanelProvider());
	}

	protected function createPagination(): ?PageNavigation
	{
		return (new PaginationFactory($this, $this->getPaginationStorage()))->create();
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BIConnector\Superset\Filter\Provider\UnusedElementsFilterProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}
}
