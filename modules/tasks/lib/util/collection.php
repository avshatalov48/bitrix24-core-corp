<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Type\Dictionary;
use Bitrix\Tasks\Item\Exporter\Canonical;
use Bitrix\Tasks\Util;

class Collection extends Dictionary
{
	/** @var Error\Collection|null  */
	private $errors = null;

	public function isSuccess()
	{
		return $this->errors == null || !$this->errors->checkHasFatals();
	}

	public function getErrors()
	{
		if($this->errors == null)
		{
			$this->errors = new Error\Collection();
		}

		return $this->errors;
	}

	public function addError($code, $message, $type = Error::TYPE_FATAL, $data = null)
	{
		$this->getErrors()->add($code, $message, $type, $data);
	}

	/**
	 * @param $e
	 * @param string $message
	 * @param mixed[] $settings
	 */
	public function addException($e, $message = '', $settings = array())
	{
		if(!($e instanceof \Exception))
		{
			return;
		}

		$code = $e->getCode();
		if($code)
		{
			$code = ToUpper($code);
		}
		else
		{
			// todo: generate appropriate code from $e class, for example
			// todo: SqlException => SQL
		}

		$message = (string) $message;
		if($message == '')
		{
			$message = $e->getMessage();
		}

		if(!is_array($settings) || $settings['DUMP'] != false)
		{
			Util::log($e);
		}
		$this->getErrors()->add('EXCEPTION'.($code ? '.'.$code : ''), $message, Error::TYPE_FATAL, array('EXCEPTION' => $e));
	}

	/**
	 * Find items by condition. A new collection of the same class will be returned
	 *
	 * @param array $conditions
	 * @param mixed[]|int $limit
	 * @return static
	 */
	public function find($conditions = array(), $limit = -1)
	{
		$parameters = array();
		if(is_array($limit))
		{
			$parameters = $limit;
			$limit = -1;
		}

		if(is_array($parameters['CONTAINER']) || Collection::isA($parameters['CONTAINER']))
		{
			$filtered = $parameters['CONTAINER'];
		}
		elseif($parameters['CONTAINER'] == 'ARRAY')
		{
			$filtered = array();
		}
		else
		{
			$filtered = new static();
		}

		if(Filter::isA($conditions))
		{
			$filter = $conditions;
		}
		elseif(is_array($conditions))
		{
			$filter = new Filter($conditions);
		}
		else
		{
			return $filtered;
		}

		$count = 0;
		foreach($this->values as $k => $v)
		{
			if($filter->match($v))
			{
				$filtered->push($v);

				$count++;
				if($limit > -1 && $count > $limit)
				{
					break;
				}
			}
		}

		return $filtered;
	}

	public function findOne($conditions = array())
	{
		return $this->find($conditions, 1)->get(0);
	}

	/**
	 * Sorts this collection by $conditions
	 *
	 * @param array $conditions
	 *
	 * todo: only one sort parameter supported
	 * todo: implement option "nulls first\last" to be able to preserve original order for items that do not have
	 * todo: criteria offset at all
	 * @return static
	 */
	public function sort($conditions = array())
	{
		if(empty($conditions))
		{
			return $this;
		}

		$fields = array_keys($conditions);
		$field = $fields[0];
		$way = ToLower($conditions[0]) == 'desc' ? 0 : 1;

		$cb = function($a, $b) use ($field, $way) {

			$aVal = $a[$field];
			$bVal = $b[$field];

			if($aVal == $bVal)
			{
				return 0;
			}

			if($way > 0)
			{
				return ($aVal < $bVal) ? -1 : 1;
			}
			else
			{
				return ($aVal < $bVal) ? 1 : -1;
			}
		};

		uasort($this->values, $cb);

		// we may implement option that will preserve keys, but for now just reset them
		$this->values = array_values($this->values);

		return $this;
	}

	public function push($data)
	{
		array_push($this->values, $data);
		$this->onChange();

		return $this;
	}

	public function unShift($data)
	{
		array_unshift($this->values, $data);
		$this->onChange();
	}

	public function update(array $conditions, $data, $limit = -1)
	{
		// todo: find all items that match the conditions, then update them all with $data

		$this->onChange();
	}

	public function updateOne(array $conditions, $data)
	{
		return $this->update($conditions, $data, 1);
	}

	/**
	 * Delete several items by condition
	 *
	 * @param $conditions
	 * @param int $limit
	 * @return $this
	 */
	public function delete($conditions, $limit = -1)
	{
		if(!$this->count())
		{
			return $this;
		}

		if(Filter::isA($conditions))
		{
			$filter = $conditions;
		}
		elseif(is_array($conditions))
		{
			$filter = new Filter($conditions);
		}
		else
		{
			return $this;
		}

		$count = 0;
		foreach($this->values as $k => $v)
		{
			if($filter->match($v))
			{
				$v = null;
				unset($this->values[$k]);

				$count++;
				if($limit > -1 && $count > $limit)
				{
					break;
				}
			}
		}

		$this->onChange();

		return $this;
	}

	public function deleteOne(array $conditions)
	{
		return $this->delete($conditions, 1);
	}

	public function first()
	{
		return $this->nth(0);
	}

	public function last()
	{
		return $this->nth(count($this->values) - 1);
	}

	public function nth($num)
	{
		$i = 0;
		foreach($this->values as $item)
		{
			if($i == $num)
			{
				return $item;
			}

			$i++;
		}

		return null;
	}

	public function export($exporter = null)
	{
		if($exporter === null)
		{
			$exporter = new Canonical(); // todo: object pool here
		}

		return $exporter->export($this);
	}
}