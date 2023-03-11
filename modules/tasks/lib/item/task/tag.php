<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Item\Result;
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
		return LabelTable::getClass();
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

	public function save($settings = [])
	{
		$tags = is_string($this['NAME']) ? [$this['NAME']] : [];
		$tagService = new \Bitrix\Tasks\Control\Tag((int)$this['USER_ID']);
		$tagService->set($this['TASK_ID'], $tags, 0, 0, true);
		return new Result();
	}

	public static function find(array $parameters = [], $settings = null)
	{
		$items = [];
		$result = self::getCollectionInstance();
		$dc = self::getDataSourceClass();
		$res = $dc::getList(array_merge(['select' => ['*', 'TASK_' => 'TASKS']], $parameters));
		if ($res)
		{
			while ($item = $res->fetch())
			{
				$items[] = self::makeInstanceFromSource($item, $settings['USER_ID']);
			}
			$result->set($items);
		}

		return $result;
	}
}