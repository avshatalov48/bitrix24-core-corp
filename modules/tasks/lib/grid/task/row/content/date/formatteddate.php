<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Tasks\Grid\Task\Row\Content\Date;

/**
 * Class FormattedDate
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class FormattedDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$rowData = $this->getRowData();
		return isset($rowData[$this->fieldKey]) ? $this->formatDate($rowData[$this->fieldKey]) : '';
	}
}