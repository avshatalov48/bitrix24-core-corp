<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Tasks\Grid\Task\Row\Content\Date;

/**
 * Class ChangedDate
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class ChangedDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		return $this->formatDate($this->getRowData()['CHANGED_DATE']);
	}
}