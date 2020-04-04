<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Tasks\Item\Result;

final class TriggerState extends State
{
	protected $depth = 1; // its because __construct() do leave()
	protected $enterCb = null;
	protected $leaveCb = null;

	public function setEnterCallback($cb)
	{
		if(is_callable($cb))
		{
			$this->enterCb = $cb;
		}
	}

	public function setLeaveCallback($cb)
	{
		if(is_callable($cb))
		{
			$this->leaveCb = $cb;
		}
	}

	public function enter(array $values = array())
	{
		if(!$this->depth)
		{
			if($this->enterCb)
			{
				call_user_func_array($this->enterCb, array($this));
			}
			parent::enter($values);
		}

		$this->depth++;
	}

	public function leave()
	{
		$result = new Result();

		if($this->depth == 1)
		{
			if($this->leaveCb)
			{
				$result = call_user_func_array($this->leaveCb, array($this));
			}
			parent::leave();
		}

		$this->depth--;

		return $result;
	}

	public function fireLeaveCallback()
	{
		if(!$this->isInProgress())
		{
			if($this->leaveCb)
			{
				call_user_func_array($this->leaveCb, array($this));
			}
		}
	}

	public function accumulateArray($name, array $items = array())
	{
		$name = trim((string) $name);
		if($name != '')
		{
			$this->values[$name] = array_unique(array_merge(
				is_array($this[$name]) ? $this[$name] : array(),
				$items
			));
		}
	}
	public function getArray($name)
	{
		$result = array();

		$name = trim((string) $name);
		if($name != '' && is_array($this[$name]))
		{
			$result = $this->values[$name];
		}

		return $result;
	}
}