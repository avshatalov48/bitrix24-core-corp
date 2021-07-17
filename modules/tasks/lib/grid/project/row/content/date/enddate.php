<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content\Date;

use Bitrix\Tasks\Grid\Project\Row\Content\Date;

/**
 * Class EndDate
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content\Date
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