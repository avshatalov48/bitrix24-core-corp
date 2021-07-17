<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Tasks\Grid\Task\Row\Content\Date;

/**
 * Class CreatedDate
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class CreatedDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		return $this->formatDate($this->getRowData()['CREATED_DATE']);
	}
}