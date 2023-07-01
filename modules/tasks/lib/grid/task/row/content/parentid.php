<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class ParentId
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class ParentId extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		return (string) ($this->getRowData()['PARENT_ID'] ?? '');
	}
}