<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Util\Replicator;

use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Item;
use Bitrix\Main\NotImplementedException;

abstract class Task
{
	protected $converter = null;

	/**
	 * @var array
	 * @access private
	 */
	protected $config = array(); // todo: configure object here later

	/**
	 * Returns source item class
	 *
	 * @return Item
	 * @throws NotImplementedException
	 */
	protected static function getSourceClass()
	{
		throw new NotImplementedException('No default source item class');
	}

	/**
	 * Returns default converter class
	 *
	 * @return string
	 * @throws NotImplementedException
	 */
	protected static function getConverterClass()
	{
		throw new NotImplementedException('No default converter class');
	}

	public function setConverter($converter)
	{
		$this->converter = $converter;
	}

	/**
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return \Bitrix\Tasks\Item\Converter
	 */
	public function getConverter()
	{
		if($this->converter === null)
		{
			$cClass = static::getConverterClass();

			$this->converter = new $cClass();
		}

		return $this->converter;
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
	 * @param int|\Bitrix\Tasks\Item $source
	 * @param int $userId
	 * @param array $parameters
	 * @return \Bitrix\Tasks\Util\Replicator\Result
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function produce($source, $userId = 0, array $parameters = array())
	{
		$result = new Result();

		Item\Task::enterBatchState();

		// create ROOT task
		$srcInstance = $this->getSourceInstance($source, $userId);
		$dstInstance = null;
		$dataMixin = array_merge(is_array($parameters['OVERRIDE_DATA']) ? $parameters['OVERRIDE_DATA'] : array(), $this->getStaticDataMixin());

		if($this->isMultitaskSource($srcInstance, $parameters)) // in case of multitasking there will be several "root" tasks
		{
			$dataMixin['RESPONSIBLE_ID'] = $srcInstance['CREATED_BY'];
		}

		$saveResult = $this->saveItemFromSource($source, $dataMixin, $userId);
		if($saveResult->isSuccess())
		{
			$dstInstance = $saveResult->getInstance();
			$result->setInstance($dstInstance);
		}

		$result->getErrors()->load($saveResult->getErrors());

		// create SUB tasks
		if($dstInstance && $result->isSuccess())
		{
			// the result of creating sub-items
			$subResult = $this->produceSub($srcInstance, $dstInstance, $parameters, $userId);
			$sIResults = $subResult->getData();

			if($sIResults)
			{
				// save sub-item collection
				$result->setSubInstanceResult($sIResults);
			}

			// get all errors from $subResult, but as warnings
			$result->loadErrors($subResult->getErrors()->transform(array('TYPE' => Error::TYPE_WARNING)));
		}

		Item\Task::leaveBatchState();

		return $result;
	}

	public function produceSub($source, $destination, array $parameters = array(), $userId = 0)
	{
		$result = new Result();
		$result->setData(new Util\Collection());

		return $result;
	}

	protected function getStaticDataMixin()
	{
		return array();
	}

	protected function isMultitaskSource($source, array $parameters)
	{
		return !array_key_exists('MULTITASKING', $parameters) || $parameters['MULTITASKING'] != false; // multitasking was not disabled
	}

	/**
	 * @param mixed[]|Item $source
	 * @param int $userId
	 * @return null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function getSourceInstance($source, $userId = 0)
	{
		if(Item::isA($source))
		{
			return $source;
		}
		elseif(is_array($source))
		{
			$item = $this->makeSourceInstance(0, $userId); // created source instance
			$item->setData($source);

			return $item;
		}
		elseif(intval($source) == $source)
		{
			return $this->makeSourceInstance($source, $userId);
		}

		return null;
	}

	protected function getDestinationInstance($destination, $userId = 0)
	{
		$itemClass = $this->getConverter()->getTargetItemClass();

		if(is_object($destination))
		{
			return $destination;
		}
		elseif(is_array($destination))
		{
			$item = new $itemClass(0, $userId);
			$item->setData($destination);

			return $item;
		}
		elseif(intval($destination) == $destination)
		{
			return new $itemClass(intval($destination), $userId);
		}

		return null;
	}

	/**
	 * @param $id
	 * @param $userId
	 * @return Item
	 * @throws NotImplementedException
	 */
	protected function makeSourceInstance($id, $userId)
	{
		/** @var Item $itemClass */
		$itemClass = static::getSourceClass();

		return new $itemClass(intval($id), $userId);
	}

	/**
	 * @param Item $source
	 * @param $dataMixin
	 * @param int $userId
	 * @return Result
	 */
	protected function saveItemFromSource($source, $dataMixin, $userId = 0)
	{
		/** @var Item $source */
		$source = $this->getSourceInstance($source, $userId);
		$converter = $this->getConverter();
		$dstInstance = null;

		$creationResult = new Result();

		$conversionResult = $source->transform($converter); // converted to the destination instance
		if($conversionResult->isSuccess()) // was able to produce an item
		{
			$dstInstance = $conversionResult->getInstance();
			$dstInstance->setData($dataMixin);

			$saveResult = $dstInstance->save();
			if(!$saveResult->isSuccess()) // but was not able to save it
			{
				$dstInstance->abortTransformation($this->getConverter()); // rolling back possible temporal data creation
			}

			if(!$saveResult->getErrors()->isEmpty())
			{
				$creationResult->getErrors()->load($saveResult->getErrors());
			}
		}
		else
		{
			if(!$conversionResult->getErrors()->isEmpty())
			{
				$creationResult->getErrors()->load($conversionResult->getErrors());
			}
		}

		$creationResult->setInstance($dstInstance);

		return $creationResult;
	}
}