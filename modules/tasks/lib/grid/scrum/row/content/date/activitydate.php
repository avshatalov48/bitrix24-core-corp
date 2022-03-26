<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content\Date;

use Bitrix\Tasks\Grid\Scrum\Row\Content\Date;

/**
 * Class ActivityDate
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content\Date
 */
class ActivityDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();
		$activityDate = $this->formatDate($row['ACTIVITY_DATE']);

		return "<span style='margin-left: 3px'>{$activityDate}</span>";
	}
}