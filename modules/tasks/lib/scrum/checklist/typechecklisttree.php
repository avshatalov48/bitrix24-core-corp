<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable;

class TypeCheckListTree extends CheckListTree
{
	public static function getDataController()
	{
		return TypeChecklistTreeTable::getClass();
	}
}