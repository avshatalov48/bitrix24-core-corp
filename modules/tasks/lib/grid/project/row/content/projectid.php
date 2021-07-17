<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Tasks\Grid\Project\Row\Content;

/**
 * Class ProjectId
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class ProjectId extends Content
{
	public function prepare(): string
	{
		return (string)$this->getRowData()['ID'];
	}
}