<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content\Date;

use Bitrix\Tasks\Grid\Scrum\Row\Content\Date;

/**
 * Class EndDate
 *
 * @package Bitrix\Tasks\Grid\Scrum\Row\Content\Date
 */
class EndDate extends Date
{
	public function prepare(): string
	{
		$row = $this->getRowData();
		$startDate = ($row['PROJECT_DATE_FINISH'] ?: false);

		return $this->formatDate($startDate);
	}
}