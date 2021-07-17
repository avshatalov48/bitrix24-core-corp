<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Tasks\Grid\Task\Row\Content\Date;

/**
 * Class ClosedDate
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class ClosedDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		return $this->formatDate($this->getRowData()['CLOSED_DATE']);
	}
}