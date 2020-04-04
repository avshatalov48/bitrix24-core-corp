<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\Internals\Task\TagTable;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Item\Task\Collection;

final class Tag extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TASK_ID';
	}

	public static function getDataSourceClass()
	{
		return TagTable::getClass();
	}

	public static function getCollectionClass()
	{
		return Collection\Tag::getClass();
	}

	public function prepareData($result)
	{
		if(parent::prepareData($result))
		{
			$state = $this->getTransitionState();
			if($state->isModeCreate()) // it is add()
			{
				// set default user
				if(!$this->isFieldModified('USER_ID'))
				{
					$userId = User::getId();
					$parent = $this->getParent();
					if($parent)
					{
						$userId = $parent->getUserId();
					}

					$this['USER_ID'] = $userId;
				}
			}
		}

		return $result->isSuccess();
	}
}