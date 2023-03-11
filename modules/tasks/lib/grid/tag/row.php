<?php

namespace Bitrix\Tasks\Grid\Tag;

/**
 * Class Row
 *
 * @package Bitrix\Tasks\Grid\Tag
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
		return [];
	}

	public function prepareCounters(): array
	{
		return [];
	}
}