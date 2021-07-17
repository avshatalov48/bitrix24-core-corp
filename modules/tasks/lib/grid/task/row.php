<?php
namespace Bitrix\Tasks\Grid\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;

/**
 * Class Row
 *
 * @package Bitrix\Tasks\Grid\Task
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