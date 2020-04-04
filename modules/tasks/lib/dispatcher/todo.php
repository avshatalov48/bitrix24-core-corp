<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Util\Result;

final class ToDo implements \ArrayAccess
{
	protected $action = '';
	protected $arguments = array();
	protected $parameters = array();
	/**
	 * @var Result|null
	 */
	protected $result = null;

	public function __construct($action, $arguments, $parameters = array())
	{
		$this->setAction($action);
		$this->arguments = $arguments;

		if(is_array($parameters))
		{
			$this->parameters = array_change_key_case($parameters, CASE_UPPER);
		}
	}

	public function setResult($result)
	{
		if(Result::isA($result))
		{
			$this->result = $result;
		}
	}

	public function setAction($action)
	{
		$this->action = ToLower(trim((string) $action));
	}

	public function getAction()
	{
		return $this->action;
	}

	public function getCode()
	{
		return $this->parameters['CODE'];
	}

	public function setCode($code)
	{
		$this->parameters['CODE'] = $code;
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function setArguments($arguments)
	{
		if(is_array($arguments))
		{
			$this->arguments = $arguments;
		}
	}

	/**
	 * @return Result|null
	 */
	public function getResult()
	{
		return $this->result;
	}

	public function isProcessed()
	{
		return $this->result !== null;
	}

	public function isSuccess()
	{
		return $this->isProcessed() && $this->result->isSuccess();
	}

	public function export()
	{
		return array(
			'OPERATION' => $this->action,
			'ARGUMENTS' => $this->arguments,
			'PARAMETERS' => $this->parameters,
		);
	}

	public function offsetExists($offset)
	{
		return $offset == 'CODE' || $offset == 'ACTION' || $offset = 'ARGUMENTS';
	}

	public function offsetGet($offset)
	{
		if($offset == 'CODE')
		{
			return $this->getCode();
		}
		elseif($offset == 'ACTION')
		{
			return $this->getAction();
		}
		elseif($offset == 'ARGUMENTS')
		{
			return $this->getArguments();
		}

		return null;
	}

	public function offsetSet($offset, $value)
	{
		throw new NotImplementedException();
	}

	public function offsetUnset($offset)
	{
		throw new NotImplementedException();
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function isA($object)
	{
		return is_a($object, static::getClass());
	}
}