<?php
namespace Bitrix\Tasks\Grid\Effective\Row\Content\Date;

use Bitrix\Tasks\Grid\Effective\Row\Content\Date;

/**
 * Class FormattedDate
 * @package Bitrix\Tasks\Grid\Effective\Row\Content\Date
 */
class FormattedDate extends Date
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$rowData = $this->getRowData();
		return $this->formatDate($rowData[$this->fieldKey]);
	}
}