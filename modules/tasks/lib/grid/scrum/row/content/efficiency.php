<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content;

use Bitrix\Tasks\Grid\Scrum\Row\Content;

/**
 * Class Efficiency
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Efficiency extends Content
{
	public function prepare(): string
	{
		return "<div class='tasks-projects-percent'>{$this->getRowData()['EFFICIENCY']}%</div>";
	}
}