<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class TimeEstimate
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class TimeEstimate extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		return sprintf(
			'%02d:%02d',
			floor(($row['TIME_ESTIMATE'] ?? 0) / 3600),
			floor(($row['TIME_ESTIMATE'] ?? 0) / 60) % 60
		);
	}
}