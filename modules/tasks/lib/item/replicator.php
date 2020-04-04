<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 *
 *
 *
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Collection;

/**
 * Class Replicator
 * @package Bitrix\Tasks\Item
 */

abstract class Replicator
{
	protected $converter = null;

	/**
	 * Returns source item class
	 *
	 * @return string
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected static function getItemClass()
	{
		throw new \Bitrix\Main\NotImplementedException('No default source item class');
	}

	/**
	 * Returns default converter class
	 *
	 * @return string
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected static function getConverterClass()
	{
		throw new \Bitrix\Main\NotImplementedException('No default converter class');
	}

	public function setConverter($converter)
	{
		$this->converter = $converter;
	}

	public function getConverter()
	{
		if($this->converter === null)
		{
			$cClass = static::getConverterClass();

			$this->converter = new $cClass();
		}

		return $this->converter;
	}

	public function produceFrom($sourceId, $userId = 0, array $parameters = array())
	{
		$result = new Replicator\Result();

		$converter = $this->getConverter();
		$itemClass = static::getItemClass(); // todo: check $itemClass here
		$targetClass = $converter->getTargetItemClass();

		$targetClass::enterBatchState();

		// create ROOT item

		$srcInstance = $itemClass::getInstance($sourceId, $userId);
		$dstInstance = null;
		$conversionResult = $srcInstance->transformWith($converter);

		if($conversionResult->isSuccess()) // was able to produce an item
		{
			$dstInstance = $conversionResult->getInstance();
			$dstInstance->mixData($parameters);

			$saveResult = $dstInstance->save();
			if(!$saveResult->isSuccess()) // but was not able to save it
			{
				$dstInstance->abortTransformation($this->getConverter()); // rolling back possible temporal data creation
			}

			$result->setInstance($dstInstance);

			// there could be warnings
			$result->getErrors()->load($saveResult->getErrors());
		}
		else
		{
			$result->getErrors()->load($conversionResult->getErrors());
		}

		// create SUB items

		if($dstInstance && $result->isSuccess())
		{
			// the result of creating sub-items
			$subResult = $this->produceSubItemsFrom($srcInstance, $dstInstance, $parameters, $userId);
			$sIResults = $subResult->getData();

			// save sub-item collection
			$result->setSubInstanceResult($sIResults);

			// get all errors from $subResult, but as warnings
			$result->loadErrors($subResult->getErrors()->transform(array('TYPE' => Error::TYPE_WARNING)));

			$this->doPostActions($srcInstance, $parameters);
		}

		$targetClass::leaveBatchState();

		return $result;
	}

	public function produceSubItemsFrom($source, $destination, array $parameters = array(), $userId = 0)
	{
		$result = new Result();
		$result->setData(new Collection());

		return $result;
	}

	protected function doPostActions($srcInstance, array $parameters = array())
	{
	}
}