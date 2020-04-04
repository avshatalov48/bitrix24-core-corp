<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Main\NotImplementedException;

class Access
{
	private $disable = 0;
	protected $immutable = false;

	public function allow($action)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		// todo
	}

	public function deny($action)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		// todo
	}

	/**
	 * Normally you SHOULD NOT be able to modify default access controller behaviour, so immutable flag is at our rescue
	 */
	public function setImmutable()
	{
		$this->immutable = true;
	}

	public function isImmutable()
	{
		return $this->immutable;
	}

	/**
	 * Allows every and each action
	 */
	public function disable()
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$this->disable++;
	}

	/**
	 * Restores controller`s normal behaviour
	 */
	public function enable()
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		if($this->disable >= 1)
		{
			$this->disable--;
		}
	}

	public function isEnabled()
	{
		return $this->disable == 0;
	}

	public function canCreate($item, $userId = 0)
	{
		return new Result();
	}

	public function canRead($item, $userId = 0)
	{
		return new Result();
	}

	public function canUpdate($item, $userId = 0)
	{
		return new Result();
	}

	public function canDelete($item, $userId = 0)
	{
		return new Result();
	}

	public function canFetchData($item, $userId = 0)
	{
		return new Result();
	}

	/**
	 * Alters query parameters to check access rights on database side
	 *
	 * @param mixed[]|\Bitrix\Main\Entity\Query query parameters or query itself
	 * @param mixed[] $parameters
	 * @return array
	 */
	public function addDataBaseAccessCheck($query, array $parameters = array())
	{
		return $query;
	}

	/**
	 * @return Access
	 */
	public function spawn()
	{
		return new static();
	}

	public function __call($name, array $arguments)
	{
		$name = trim((string) $name);

		if(strpos($name, 'can') === 0)
		{
			return new Result(); // unknown action, like "walk on ears" will be allowed
		}
		else
		{
			throw new NotImplementedException('Call to unknown method '.$name);
		}
	}

	public static function getClass()
	{
		return get_called_class();
	}
}