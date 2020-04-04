<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\Error;

use \Bitrix\Tasks\Util\Error;

class Collection extends \Bitrix\Main\Type\Dictionary // todo: extends \Bitrix\Tasks\Util\Type\Dictionary
{
	/**
	 * @deprecated
	 */
	const TYPE_FATAL = 		'FATAL';
	/**
	 * @deprecated
	 */
	const TYPE_WARNING = 	'WARNING';

	protected $fatalCount = 0;

	/**
	 * Adds a new error to the collection
	 *
	 * todo: provide add() from array
	 * todo: provide add() from \Bitrix\Main\Error, with default type == FATAL
	 *
	 * @param string|Error $codeOrInstance
	 * @param string $message
	 * @param bool $type
	 * @param null $additionalData
	 */
	public function add($codeOrInstance, $message = '', $type = false, $additionalData = null)
	{
		$error = null;
		if(is_object($codeOrInstance))
		{
			if($codeOrInstance instanceof Error)
			{
				$error = $codeOrInstance;
			}
			elseif($codeOrInstance instanceof \Bitrix\Main\Error)
			{
				$error = new Error($codeOrInstance->getMessage(), trim((string) $codeOrInstance->getCode()), Error::TYPE_FATAL);
			}
		}
		else
		{
			$error = new Error($message, trim((string) $codeOrInstance), $type, $additionalData);
		}

		if($error !== null)
		{
			if($error->isFatal())
			{
				$this->fatalCount++;
			}
			$this->values[] = $error;
		}
	}

	public function addWarning($code, $message = '', $additionalData = null)
	{
		$this->add($code, $message, Error::TYPE_WARNING, $additionalData);
	}

	/**
	 * Fill current collection from another collection
	 * @param $source
	 */
	public function load($source)
	{
		if($source instanceof self)
		{
			foreach($source->toArray() as $error)
			{
				$this->add($error);
			}
		}
	}

	/**
	 * Filter current collection by some condition
	 * @param array $filter
	 * @return Collection
	 */
	public function find($filter = array())
	{
		$result = new self();

		/** @var Error $error */
		foreach($this->values as $error)
		{
			if($error->matchFilter($filter))
			{
				$result->add($error);
			}
		}

		return $result;
	}

	public function transform(array $rules = array())
	{
		$result = new self();

		/** @var Error $error */
		foreach($this->values as $error)
		{
			$error = clone $error;

			if(array_key_exists('CODE', $rules))
			{
				$error->setCode(str_replace('#CODE#', $error->getCode(), $rules['CODE']));
			}
			if(array_key_exists('MESSAGE', $rules))
			{
				$error->setMessage(str_replace('#MESSAGE#', $error->getMessage(), $rules['MESSAGE']));
			}
			if(array_key_exists('TYPE', $rules))
			{
				$error->setType($rules['TYPE']);
			}
			if(array_key_exists('DATA', $rules))
			{
				$error->setData($rules['DATA']);
			}

			$result->add($error);
		}

		return $result;
	}

	public function prefixCode($prefix)
	{
		return $this->transform(array('CODE' => $prefix.'#CODE#'));
	}

	public function checkNoFatals()
	{
		return $this->fatalCount == 0;
	}

	public function checkHasFatals()
	{
		return $this->fatalCount != 0;
	}

	public function checkHasErrorOfType($type)
	{
		return !$this->find(array('TYPE' => $type))->isEmpty();
	}

	public function getMessages()
	{
		$result = array();

		/** @var Error $value */
		foreach($this->values as $value)
		{
			$result[] = $value->getMessage();
		}

		return $result;
	}

	public function getArray()
	{
		$result = array();
		/** @var Error $value */
		foreach($this->values as $value)
		{
			$result[] = $value->toArray();
		}

		return $result;
	}

	public function getArrayMeta()
	{
		$result = array();
		/** @var Error $value */
		foreach($this->values as $value)
		{
			$result[] = $value->toArrayMeta();
		}

		return $result;
	}

	public static function makeFromArray(array $errors)
	{
		$collection = new static();

		foreach($errors as $error)
		{
			$collection->add(Error::makeFromArray($error));
		}

		return $collection;
	}

	public function first()
	{
		if(!count($this->values))
		{
			return null;
		}

		return $this->values[0];
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function isA($object)
	{
		return is_a($object, static::getClass());
	}

	/**
	 * @param bool $flat
	 * @param null $filter
	 * @return array
	 *
	 * @deprecated Non-architecture-friendly method
	 */
	public function getAll($flat = false, $filter = null)
	{
		$result = array();

		if(is_object($filter) && method_exists($filter, 'process'))
		{
			$values = $filter->process($this->values);
		}
		else
		{
			$values = $this->values;
		}

		if($flat)
		{
			/** @var Error $value */
			foreach($values as $value)
			{
				if(!is_array($value))
				{
					$value = $value->toArray();
				}

				$result[] = $value;
			}
		}
		else
		{
			/** @var Error $value */
			foreach($values as $value)
			{
				if(!is_array($value))
				{
					$value = $value->toArray();
				}

				$type = $value['TYPE'];
				unset($value['TYPE']);
				$result[$type][] = $value;
			}
		}

		return $result;
	}

	/**
	 * @param $other
	 * @param array $parameters
	 *
	 * @deprecated
	 * @see \Bitrix\Tasks\Util\Error\Collection::load
	 */
	public function addForeignErrors($other, array $parameters = array('CHANGE_TYPE_TO' => false))
	{
		if($other !== null)
		{
			$parameters['CHANGE_TYPE_TO'] = (string) $parameters['CHANGE_TYPE_TO'];

			if($other instanceof Collection)
			{
				/** @var Error $error */
				foreach($other->toArray() as $error)
				{
					if($parameters['CHANGE_TYPE_TO'] != '')
					{
						$error->setType($parameters['CHANGE_TYPE_TO']);
					}

					$this->add($error);
				}
			}
			elseif(is_array($other))
			{
				foreach($other as $error)
				{
					if(!is_array($error))
					{
						$error = $error->toArray();
					}

					// old tasks crud errors
					if(array_key_exists('id', $error) && array_key_exists('text', $error))
					{
						$errorType = $parameters['CHANGE_TYPE_TO'] != '' ? $parameters['CHANGE_TYPE_TO'] : Error::TYPE_FATAL;

						$newError = array(
							'CODE' => (string) $error['id'],
							'MESSAGE' => (string) $error['text'],
							'TYPE' => $errorType
						);
					}
					else
					{
						if((string) $error['CODE'] == '')
						{
							continue;
						}

						$errorType = (string) $error['TYPE'];
						if((string) $error['TYPE'] == '')
						{
							$errorType = Error::TYPE_FATAL;
						}
						if($parameters['CHANGE_TYPE_TO'] != '')
						{
							$errorType = $parameters['CHANGE_TYPE_TO'];
						}

						$newError = array(
							'CODE' => (string) $error['CODE'],
							'MESSAGE' => (string) $error['MESSAGE'],
							'TYPE' => $errorType
						);
					}

					$this->add($newError['CODE'], $newError['MESSAGE'], $newError['TYPE']);
				}
			}
		}
	}

	/**
	 * @return bool
	 *
	 * @deprecated Use .isEmpty()
	 */
	public function checkHasErrors()
	{
		return !$this->isEmpty();
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getFatals()
	{
		return $this->getOfType(Error::TYPE_FATAL);
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getWarnings()
	{
		return $this->getOfType(Error::TYPE_WARNING);
	}

	/**
	 * @param $type
	 * @return array
	 * @deprecated
	 */
	protected function getOfType($type)
	{
		$result = array();
		foreach($this->values as $value)
		{
			if($value['TYPE'] == $type)
			{
				unset($value['TYPE']);
				$result[] = $value;
			}
		}

		return $result;
	}

	/**
	 * Filter current collection by some condition
	 * @param array $filter
	 * @return Collection
	 */
	public function filter($filter = array())
	{
		return $this->find($filter);
	}
}