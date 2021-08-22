<?php
namespace Bitrix\Tasks\Grid\Effective;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;

/**
 * Class Row
 * @package Bitrix\Tasks\Grid\Effective
 */
class Row extends \Bitrix\Tasks\Grid\Row
{
	public function prepareActions(): array
	{
		return [];
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