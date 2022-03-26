<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content;

use Bitrix\Tasks\Grid\Scrum\Row\Content;

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