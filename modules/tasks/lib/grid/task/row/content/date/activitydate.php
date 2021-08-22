<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Tasks\Grid\Task\Row\Content\Date;

/**
 * Class ActivityDate
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class ActivityDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		$date = $this->formatDate($row[$this->fieldKey]);

		return "<span id='changedDate' style='margin-left: 3px'>{$date}</span>";
	}
}