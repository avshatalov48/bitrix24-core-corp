<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Scrum\Internal\EntityChecklistTreeTable;

class EntityCheckListTree extends CheckListTree
{
	public static function getDataController()
	{
		return EntityChecklistTreeTable::getClass();
	}
}