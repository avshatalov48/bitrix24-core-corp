<?php

namespace Bitrix\Crm\Component\EntityList\Grid;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Component\EntityList\Grid\Column\Provider\AutomatedSolutionDataProvider;
use Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\AutomatedSolutionRowAssembler;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;
use Bitrix\Main\Grid\Settings;

class AutomatedSolutionGrid extends Grid
{
	public function __construct(
		Settings $settings,
		private readonly AutomatedSolutionManager $automatedSolutionManager,
		private readonly UserPermissions $userPermissions,
		private readonly Router $router,
	)
	{
		parent::__construct($settings);
	}

	protected function createColumns(): Columns
	{
		return new Columns(
			new AutomatedSolutionDataProvider(),
		);
	}

	protected function createRows(): Rows
	{
		return new Rows(
			new AutomatedSolutionRowAssembler($this->getVisibleColumnsIds()),
			new Row\Action\AutomatedSolutionDataProvider(
				$this->getSettings(),
				$this->automatedSolutionManager,
				$this->userPermissions,
				$this->router,
			),
		);
	}

	protected function createFilter(): ?Filter
	{
		return new \Bitrix\Crm\Filter\Filter(
			$this->getId(),
			new \Bitrix\Crm\Filter\AutomatedSolutionDataProvider($this->getId()),
		);
	}
}
