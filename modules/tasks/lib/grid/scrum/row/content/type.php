<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Scrum\Row\Content;

Loc::loadMessages(__FILE__);

/**
 * Class Type
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Type extends Content
{
	public function prepare(): string
	{
		$row = $this->getRowData();

		$type = Loc::getMessage('TASKS_GRID_SCRUM_ROW_CONTENT_TYPE_PUBLIC');
		if ($row['OPENED'] === 'N')
		{
			$type = Loc::getMessage('TASKS_GRID_SCRUM_ROW_CONTENT_TYPE_PRIVATE');
		}

		return $type;
	}
}