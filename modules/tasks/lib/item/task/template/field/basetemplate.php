<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Template\Field;

use Bitrix\Tasks\Internals\Helper\Task\Template\Dependence;
use Bitrix\Tasks\Internals\Task\Template\DependenceTable;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Internals\DataBase\Tree;
use Bitrix\Tasks\Item\Task\Template;

class BaseTemplate extends \Bitrix\Tasks\Item\Field\Scalar
{
	public function readValueFromDatabase($key, $item)
	{
		$id = $item->getId();

		if($id)
		{
			$item = DependenceTable::getList(array(
				'filter' => array(
					'=DIRECT' => '1',
					'=TEMPLATE_ID' => $id
				),
				'limit' => 1
			))->fetch();

			if($item && $item['PARENT_TEMPLATE_ID'])
			{
				return (int) $item['PARENT_TEMPLATE_ID'];
			}
		}

		return null;
	}

	public function checkValue($value, $key, $item, array $parameters = array())
	{
		if(parent::checkValue($value, $key, $item, $parameters))
		{
			$id = $item->getId();
			if($id)
			{
				$aResult = Dependence::canAttach($id, $value);
				if(!$aResult->isSuccess())
				{
					$result = static::obtainResultInstance($parameters);
					if($result)
					{
						$result->adoptErrors($aResult);
					}

					return false;
				}
			}

			return true;
		}

		return false;
	}

	public function saveValueToDataBase($value, $key, $item)
	{
		$result = new Result();

		$itemId = $item->getId();
		$state = $item->getTransitionState();
		$isAdd = $state->isModeCreate();
		$isUpdate = $state->isModeUpdate();
		$isDelete = $state->isModeDelete();

		if($itemId)
		{
			// todo: use \Bitrix\Tasks\Internals\Helper\Task\Template\Dependence here, because
			// todo: tree functional of \Bitrix\Tasks\Internals\Task\Template\DependenceTable is deprecated, it is non-extensible, buggy and
			// todo: throws exceptions, which is also not good

			try
			{
				if($isAdd || $isUpdate)
				{
					if($isAdd && $value)
					{
						// add link
						DependenceTable::createLink($itemId, $value);
					}

					if($isUpdate)
					{
						// relocate or remove link
						DependenceTable::link($itemId, $value);
					}
				}
				elseif($isDelete)
				{
					// delete the entire sub-tree
					$subRes = DependenceTable::getSubTree($itemId, array('select' => array('ID' => 'TEMPLATE_ID')), array('INCLUDE_SELF' => false));
					while($subItem = $subRes->fetch())
					{
						$subTemplate = new Template($subItem['ID']);
						if($subTemplate->canDelete())
						{
							$subDeleteResult = $subTemplate->delete(array(
								'IGNORE' => array(
									'BASE_TEMPLATE_ID'
								)
							));
							if(!$subDeleteResult->isSuccess())
							{
								$result->adoptErrors($subDeleteResult, array(
									'CODE' => 'SUB_TEMPLATE_DELETE.#CODE#',
									//'MESSAGE' => Loc::getMessage('TASKS_ITEM_SUBITEM_DELETE_ERROR').': #MESSAGE#',
								));
							}
						}
						// todo: else we could add some warning here...
					}

					// todo: actually, we must break links according to the rights...
					try
					{
						DependenceTable::deleteSubtree($itemId);
					}
					catch(Tree\TargetNodeNotFoundException $e) // had no children actually
					{
						// nobody cares
					}
				}
			}
			catch(Tree\LinkExistsException $e)
			{
				// do nothing, we are fine
			}
			catch(Tree\LinkNotExistException $e)
			{
				// do nothing, we are fine
			}
			catch(Tree\Exception $e)
			{
				$result->addException($e, 'Error managing links');
			}
		}

		return $result;
	}

	public function translateValueFromOutside($value, $key, $item)
	{
		return intval($value);
	}
}