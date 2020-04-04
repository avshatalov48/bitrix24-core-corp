<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Dictionary;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\UI;

class State extends Dictionary
{
	protected $isInside = false;
	protected $result = null;
	protected $time = 0;
	protected $mode = 0;
	protected $parameters = null;

	const MODE_CREATE = 1;
	const MODE_UPDATE = 2;
	const MODE_DELETE = 3;

	public function __construct(array $values = null)
	{
		parent::__construct($values);
		$this->leave();
	}

	public function enter(array $values = array(), $mode = 1, $parameters = null)
	{
		$this->isInside = true;
		$this->values = $values;
		$this->mode = $mode;
		$this->time = User::getTime(); // get current user`s local time
		$this->parameters = $parameters;
	}

	public function leave()
	{
		$this->isInside = false;
		$this->values = array();
		$this->time = 0;
		$this->result = new Result();
		$this->parameters = null;
	}

	public function isInProgress()
	{
		return $this->isInside;
	}

	public function isModeCreate()
	{
		return $this->mode == static::MODE_CREATE;
	}

	public function isModeUpdate()
	{
		return $this->mode == static::MODE_UPDATE;
	}

	public function isModeDelete()
	{
		return $this->mode == static::MODE_DELETE;
	}

	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @return Result
	 */
	public function getResult()
	{
		return $this->result;
	}

	public function getEnterTime()
	{
		return $this->time;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function getEnterTimeObject()
	{
		return DateTime::createFromTimestamp($this->time);
	}

	public function getEnterTimeFormatted()
	{
		return UI::formatDateTime($this->time);
	}

	public function getEnterTimeAsDateTime()
	{
		return DateTime::createFromUserTime($this->getEnterTimeFormatted());
	}

	public function containsKey($key)
	{
		return array_key_exists($key, $this->values);
	}

	public function setValue($key, $value)
	{
		$this->values[$key] = $value;
	}
}