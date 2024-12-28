<?php

namespace Bitrix\AI\ShareRole\Components\Grid;

use Bitrix\AI\ShareRole\Components\Filter\ShareRoleDataProvider;
use Bitrix\AI\ShareRole\Components\Grid\Column\DataProvider\ShareRoleProvider;
use Bitrix\AI\ShareRole\Components\Grid\Panel\Action\ShareRolePanelProvider;
use Bitrix\AI\ShareRole\Components\Grid\Row\RowAssembler\ShareRoleRowAssembler ;
use Bitrix\AI\ShareRole\Components\Grid\Row\Action\ShareRoleProvider as RoleActionProvider;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Panel;
use Bitrix\Main\Grid\Row\Rows;

class ShareRoleGrid extends Grid
{
	/**
	 * @inheritDoc
	 */
	protected function createColumns(): Columns
	{
		return new Columns(new ShareRoleProvider());
	}

	/**
	 * @inheritDoc
	 */
	protected function createRows(): Rows
	{
		return new Rows(
			new ShareRoleRowAssembler($this->getVisibleColumnsIds()),
			new RoleActionProvider(),
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new ShareRoleDataProvider($this->getId())
		);
	}

	protected function createPanel(): ?Panel
	{
		return new Panel(new ShareRolePanelProvider());
	}
}