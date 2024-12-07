<?php

namespace Bitrix\AI\SharePrompt\Components\Grid;

use Bitrix\AI\SharePrompt\Components\Filter\SharePromptDataProvider;
use Bitrix\AI\SharePrompt\Components\Grid\Panel\Action\SharePromptPanelProvider;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Action\SharePromptProvider as PromptActionProvider;
use Bitrix\AI\SharePrompt\Components\Grid\Row\RowAssembler\SharePromptRowAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Column\DataProvider\SharePromptProvider;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Panel\Panel;
use Bitrix\Main\Grid\Row\Rows;

class SharePromptGrid extends Grid
{
	/**
	 * @inheritDoc
	 */
	protected function createColumns(): Columns
	{
		return new Columns(new SharePromptProvider());
	}

	/**
	 * @inheritDoc
	 */
	protected function createRows(): Rows
	{
		return new Rows(
			new SharePromptRowAssembler($this->getVisibleColumnsIds()),
			new PromptActionProvider()
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new SharePromptDataProvider($this->getId())
		);
	}

	protected function createPanel(): ?Panel
	{
		return new Panel(new SharePromptPanelProvider());
	}
}
