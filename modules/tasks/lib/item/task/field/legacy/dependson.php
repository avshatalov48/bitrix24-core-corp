<?
/**
 * This stands for related (previous) tasks, not for Gantt links or smth else
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Field\Legacy;

use Bitrix\Tasks\Internals\Task\RelatedTable;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Result;

// todo: lately DependsOn should be an alias for SE_RELATED
// todo: we could use \Bitrix\Tasks\Item\Field\Collection\Item here, but this entity does not have primary key

class DependsOn extends \Bitrix\Tasks\Item\Field\Collection
{
	/**
	 * @param $key
	 * @param Item $item
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function readValueFromDatabase($key, $item)
	{
		$result = array();

		$id = $item->getId();
		if($id)
		{
			$res = RelatedTable::getList(array('filter' => array(
				'=TASK_ID' => $id
			), 'select' => array(
				'DEPENDS_ON_ID'
			)));
			while($item = $res->fetch())
			{
				$result[] = $item['DEPENDS_ON_ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public function saveValueToDataBase($value, $key, $item)
	{
		$result = new Result();

		$id = $item->getId();
		if($id)
		{
			$existed = RelatedTable::getList(array('filter' => array('=TASK_ID' => $id)))->fetchAll();
			foreach($existed as $eItem)
			{
				$result->adoptErrors(RelatedTable::delete($eItem));
			}

			if($value)
			{
				$value = array_unique($value->toArray());
				foreach($value as $dependsOnId)
				{
					$result->adoptErrors(RelatedTable::add(array(
						'TASK_ID' => $id,
						'DEPENDS_ON_ID' => $dependsOnId
					)));
				}
			}
		}

		return $result;
	}
}