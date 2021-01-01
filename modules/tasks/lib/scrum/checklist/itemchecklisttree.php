<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable;

class ItemCheckListTree extends CheckListTree
{
	public static function getDataController()
	{
		return ItemChecklistTreeTable::getClass();
	}
}