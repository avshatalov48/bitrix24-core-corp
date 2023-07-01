<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util;

abstract class SubItem extends \Bitrix\Tasks\Item
{
	/** @var Item|null $parent */
	protected $parent = null;
	private $parentId = 0;

	/**
	 * Returns field name that is used to connect entity with its parent in database (i.e. foreign key)
	 *
	 * @throws NotImplementedException
	 * @return string
	 */
	protected static function getParentConnectorField()
	{
		throw new NotImplementedException('No parent connector field defined');
	}

	protected static function getParentClass()
	{
		throw new NotImplementedException('No parent class defined');
	}

	public function getParent()
	{
		if($this->parent)
		{
			return $this->parent;
		}
		else
		{
			// we can fetch parent if we know its ID
			$parentId = $this->getParentId();
			if($parentId)
			{
				$parentClass = static::getParentClass();
				$parent = new $parentClass($parentId);

				$this->parent = $parent;

				return $parent;
			}
		}

		return null;
	}

	/**
	 * @param Item|null $instance
	 * @throws ArgumentException
	 */
	public function setParent($instance)
	{
		if($instance !== null)
		{
			if(!is_a($instance, '\\Bitrix\\Tasks\\Item'))
			{
				throw new ArgumentException('Illegal parent instance passed');
			}
		}

		$this->parent = $instance;
		$this->setParentId($instance->getId()); // 0 or effective ID
	}

	public function setParentId($id)
	{
		$id = intval($id);

//		if(!$id)
//		{
//			$this->parentId = 0;
//		}
//		else
//		{
//			$this->parentId = Assert::expectIntegerNonNegative($id, '$id'); // todo: do we need exception here?
//			$this->values[static::getParentConnectorField()] = $this->parentId;
//			$this->setFieldModified(static::getParentConnectorField());
//		}

		$this->parentId = $id;
		$this->values[static::getParentConnectorField()] = $this->parentId;
		$this->setFieldModified(static::getParentConnectorField());
	}

	public function getParentId()
	{
		if($this->parentId)
		{
			return $this->parentId;
		}
		elseif($this->parent)
		{
			return $this->parent->getId();
		}
		else
		{
			return intval($this[static::getParentConnectorField()]);
		}
	}

	public function getUserId()
	{
		// get from current instance
		if($this->userId)
		{
			return $this->userId;
		}

		// get from current instance parent
		if($this->parent !== null)
		{
			return $this->parent->getUserId();
		}

		// get default
		return $this->getContext()->getUserId();
	}

	public function prepareData($result)
	{
		if($res = parent::prepareData($result))
		{
			$id = $this->getId();
			$field = static::getParentConnectorField();

			// check for correct parent defined
			if(!$id && !$this[$field])
			{
				// this is CREATE and no foreign key passed. it is ok, we can define it by ourselves
				$parent = $this->getParent();
				if($parent)
				{
					$parentId = $parent->getId();
					if(!$parentId)
					{
						$result->getErrors()->add('ILLEGAL_PARENT_ID', 'Attempting to save sub-items without saving parent item, huh?');
					}
					else
					{
						$this[$field] = $parentId;
					}
				}
			}
		}

		return $res;
	}

	/**
	 * @param $parentId
	 * @param array $parameters
	 * @param null $settings
	 * @return Collection
	 */
	public static function findByParent($parentId, array $parameters = array(), $settings = null)
	{
		$parentId = intval($parentId); // todo: this wont work in case of compound or non-integer primary

		if(!$parentId)
		{
			return static::getCollectionInstance(); // if no parent id passed, just return empty collection, no exceptions!
		}

		if (
			!isset($parameters['filter'])
			|| !is_array($parameters['filter'])
		)
		{
			$parameters['filter'] = [];
		}
		$parameters['filter'] = static::getBindCondition($parentId) + $parameters['filter'];

		return static::find($parameters, $settings);
	}

	public static function deleteByParent($parentId, array $parameters = array(), $settings = null)
	{
		$result = new Result();

		$items = static::findByParent($parentId, $parameters);
		$wereErrors = false;
		$dResults = new Util\Collection();
		/** @var Item $item */
		foreach($items as $item)
		{
			$dResult = $item->delete();
			if(!$dResult->isSuccess())
			{
				$wereErrors = true;
			}

			$dResults->push($dResult);
		}

		if($wereErrors)
		{
			$result->addWarning('ACTION_INCOMPLETE', 'Some of the items was not removed properly');
		}

		$result->setData($dResults);

		return $result;
	}

	protected static function getBindCondition($parentId)
	{
		return array('='.static::getParentConnectorField() => $parentId);
	}

	protected function callCanMethod($name, $arguments)
	{
		// todo: refactor this, because, actually, there should be special Access class for SubItem, which can be switched off manually

		$parent = $this->getParent();
		if($parent === null) // no parent, then we can do everything we want, but this is odd
		{
			return true;
		}

		$transState = $parent->getTransitionState();
		if($transState && $transState->isInProgress())
		{
			// parent is in transition state, everything is allowed
			return true;
		}

		if($name == 'cancreate' || $name == 'canupdate' || $name == 'candelete')
		{
			// ask parent if we can edit it
			return call_user_func_array(array($parent, 'canUpdate'), $arguments);
		}

		return true;
	}
}