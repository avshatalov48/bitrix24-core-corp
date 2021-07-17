<?php
namespace Bitrix\Tasks\Grid\Project;

/**
 * Class Row
 *
 * @package Bitrix\Tasks\Grid\Project
 */
class Row extends \Bitrix\Tasks\Grid\Row
{
	public function prepareActions(): array
	{
		return (new Row\Action($this->getData(), $this->getParameters()))->prepare();
	}

	public function prepareContent(): array
	{
		return (new Row\Content($this->getData(), $this->getParameters()))->prepare();
	}

	public function prepareCellActions(): array
	{
		return (new Row\CellAction($this->getData(), $this->getParameters()))->prepare();
	}

	public function prepareCounters(): array
	{
		return (new Row\Counter($this->getData(), $this->getParameters()))->prepare();
	}
}