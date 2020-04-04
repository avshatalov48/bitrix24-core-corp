<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Context;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Item\Result;

class Access
{
	private $disable = 0;

	public function disable()
	{
		$this->disable++;
	}

	public function enable()
	{
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
}