<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Id
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class TaskId extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		return (string)$this->getRowData()['ID'];
	}
}