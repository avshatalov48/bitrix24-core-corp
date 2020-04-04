<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher\ToDo;

use Bitrix\Tasks\Dispatcher\ToDo;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\Error\Filter;

final class Plan extends Collection
{
	public function export($exporter = null)
	{
		$result = array();
		/** @var ToDo $op */
		foreach($this->values as $op)
		{
			$result[] = $op->export();
		}

		return $result;
	}

	/**
	 * @param $code
	 * @return ToDo|null
	 */
	public function getOperationByCode($code)
	{
		/** @var ToDo $op */
		foreach($this->values as $op)
		{
			if($op->getCode() == $code)
			{
				return $op;
			}
		}

		return null;
	}

	public function push($data)
	{
		$this->ensureHasCode($data);

		return parent::push($data);
	}

	public function addToDo($operation, $arguments = array(), $parameters = array())
	{
		$this[] = new ToDo($operation, $arguments, $parameters);
	}

	public function offsetSet($offset, $value)
	{
		$this->ensureHasCode($value);
		parent::offsetSet($offset, $value);
	}

	public function import($todoList)
	{
		if(is_array($todoList))
		{
			foreach($todoList as $op)
			{
				if(is_array($op))
				{
					// todo: submit version and context through $op['PARAMETERS']
					$this->addToDo($op['OPERATION'], $op['ARGUMENTS'], $op['PARAMETERS']);
				}
			}
		}
	}

	public function exportResult()
	{
		$result = array();
		/** @var ToDo $op */
		foreach($this->values as $op)
		{
			if($op->isProcessed())
			{
				$opResult = $op->getResult();

				$result[$op->getCode()] = array(
					'OPERATION' => 	$op->getAction(),
					'ARGUMENTS' => 	$op->getArguments(),
					'RESULT' => 	$opResult->getData(),
					'SUCCESS' => 	$opResult->getErrors()->checkNoFatals(),
					'ERRORS' => 	$opResult->getErrors()->getAll(true, new Filter())
				);
			}
		}

		return $result;
	}

	public function replaceThis($part)
	{
		/** @var ToDo $op */
		foreach($this->values as $op)
		{
			$action = $op->getAction();
			if(strpos('this.', $action) == 0)
			{
				$op->setAction(preg_replace('#^\s*this\.#', $part.'.', $action));
			}
		}
	}

	private function ensureHasCode($op)
	{
		if(ToDo::isA($op))
		{
			$code = $op->getCode();
			if(!$code)
			{
				$op->setCode('op_'.$this->count());
			}
		}
	}
}