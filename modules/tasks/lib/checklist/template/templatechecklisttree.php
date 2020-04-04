<?php
namespace Bitrix\Tasks\CheckList\Template;

use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable;

/**
 * Class TemplateCheckListTree
 *
 * @package Bitrix\Tasks\CheckList\Template
 */
class TemplateCheckListTree extends CheckListTree
{
	/**
	 * @return string
	 */
	public static function getDataController()
	{
		return CheckListTreeTable::getClass();
	}
}