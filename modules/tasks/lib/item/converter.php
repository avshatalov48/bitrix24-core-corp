<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\NotImplementedException;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Filter;
use Bitrix\Tasks\Item\Field;

abstract class Converter
{
	protected $subConverterCache = array();
	protected $context = null;
	private $config = array(); // todo: configure object here later

	/**
	 * Returns destination item class
	 *
	 * @return Item
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public static function getTargetItemClass()
	{
		throw new NotImplementedException('No default destination item class');
	}

	/**
	 * Returns a map of converter classes for sub-entities
	 *
	 * @return array
	 */
	protected static function getSubEntityConverterClassMap()
	{
		// only the following sub-entites will be converted
		return array();
	}

	public function setConfig($field, $value)
	{
		$this->config[$field] = $value;
	}

	public function getConfig($field)
	{
		return $this->config[$field];
	}

	/**
	 * Do item data conversion (main data + user fields)
	 *
	 * todo: allow $newInstance to be acceptable from outside, to be able to copy data into the previously-existed instance
	 *
	 * @param Item $instance
	 * @return Converter\Result
	 */
	public function convert($instance)
	{
		$result = new Converter\Result();

		$dstClass = $this->getTargetItemClass();
		$newInstance = null;
		$userId = $instance->getUserId();
		$data = array();

		if(!Item::isA($instance))
		{
			$result->getErrors()->add('ILLEGAL_SOURCE_INSTANCE', 'Illegal source instance');
		}
		else
		{
			$data = $instance->getData(); // will get all data: fields, user fields, sub-item collections, etc...
			if(empty($data))
			{
				$result->getErrors()->add('NO_SOURCE_DATA', 'No source data to work with (does item even exist?)');
			}
		}

		if($result->isSuccess())
		{
			/** @var Item $newInstance */
			$newInstance = new $dstClass(0, $userId);

			// step 1: convert base data
			$newData = $this->transformData($data, $instance, $newInstance, $result);

			if(is_array($newData))
			{
				$newInstance->setData($newData);
			} // else data was set inside transformData() (backward compatibility)

			// step 2: convert user fields
			$this->convertUserFieldData($instance, $newInstance, $result);

			// step 3: convert sub-entities
			$dstMap = $newInstance->getMap();
			/**
			 * @var Item\Field\Scalar $v
			 */
			foreach($dstMap as $k => $v)
			{
				if(Field\Collection::isA($v)) // not implemented for other types
				{
					$subConverter = $this->getConverterForSubEntity($k);

					if(is_object($subConverter))
					{
						$subData = array();
						foreach($data[$k] as $j)
						{
							$subResult = $subConverter->convert($j);
							if($subResult->isSuccess())
							{
								$subData[] = $subResult->getInstance();
							}
							$result->getErrors()->load($subResult->getErrors()->prefixCode($k.'.'));
						}

						if(!empty($subData))
						{
							$newInstance[$k] = $subData;
						}
					}
				}
			}
		}

		$result->setInstance($newInstance);
		$result->setConverter($this);

		return $result;
	}

	/**
	 * Dispose temporal resources taken with the conversion made on this item
	 *
	 * @param Item $instance
	 * @return Result
	 */
	public function abortConversion($instance)
	{
		$result = new Result();

		if(Item::isA($instance))
		{
			if(!$instance->isAttached())
			{
				// do dispose
				$srcUFCtrl = $instance->getUserFieldController();
				if($srcUFCtrl)
				{
					$srcUFCtrl->cancelCloneValues($instance->getData(), $instance->getId());
				}

				// todo: also abort for all sub-entities
			}
			else
			{
				$result->getErrors()->add('ALREADY_SAVED', 'Item was already saved or is not a product of conversion at all');
			}
		}

		return $result;
	}

	/**
	 * Returns current environment context (from pool or from property, if was set manually)
	 *
	 * @return null|Context
	 */
	public function getContext()
	{
		if(!$this->context)
		{
			$this->context = Context::getDefault();
		}

		return $this->context;
	}

	/**
	 * Set environment context manually
	 *
	 * @param $ctx
	 */
	public function setContext($ctx)
	{
		$this->context = $ctx;
	}

	/**
	 * @param array $data
	 * @param Item $srcInstance
	 * @param Item $dstInstance
	 * @param Result $result
	 * @return array|null
	 */
	protected function transformData(array $data, $srcInstance, $dstInstance, $result)
	{
		return array(); // produce new data on the basis of $data
	}

	/**
	 * @param Item $srcInstance
	 * @param Item $dstInstance
	 * @param Result $result
	 */
	protected function convertUserFieldData($srcInstance, $dstInstance, $result)
	{
		$srcUFCtrl = $srcInstance->getUserFieldController();
		$dstUFCtrl = $dstInstance->getUserFieldController();

		if($srcUFCtrl && $dstUFCtrl)
		{
			$parameters = array();
			if($this->config['UF.FILTER'])
			{
				$parameters['FILTER'] = new Filter($this->config['UF.FILTER']);
			}

			$dstScheme = $dstUFCtrl->getScheme();
			$newData = $dstInstance->getData('~');
			foreach($dstScheme as $fieldName => $fieldData)
			{
				// plus all user fields
				if($srcUFCtrl->isFieldExist($fieldName))
				{
					$newData[$fieldName] = $srcInstance[$fieldName];
				}
			}

			// then clone the remaining
			$ufCloneResult = $srcUFCtrl->cloneValues($newData, $dstUFCtrl, $srcInstance->getUserId(), $parameters);
			if(!$ufCloneResult->getErrors()->isEmpty())
			{
				$result->getErrors()->load($ufCloneResult->getErrors());
			}

			if($ufCloneResult->isSuccess())
			{
				$newData = $ufCloneResult->getData();
				if(is_array($newData))
				{
					foreach($newData as $field => $value)
					{
						$dstInstance[$field] = $value;
					}
				}
			}
		}
	}

	/**
	 * @param $name
	 * @return Converter|null
	 * @throws ArgumentTypeException
	 */
	protected function getConverterForSubEntity($name)
	{
		if(!array_key_exists($name, $this->subConverterCache))
		{
			$map = $this->getSubEntityConverterClassMap();

			if(isset($map[$name]))
			{
				$cClass = trim((string) $map[$name]['class']);

				if($cClass == '')
				{
					throw new ArgumentTypeException('No converter class specified');
				}
				else
				{
					$converter = new $cClass();
					if(!Converter::isA($converter))
					{
						throw new ArgumentTypeException($cClass.' is not a converter');
					}

					$this->subConverterCache[$name] = $converter;
				}
			}
		}

		return $this->subConverterCache[$name];
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