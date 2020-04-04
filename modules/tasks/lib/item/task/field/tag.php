<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Field;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Item\SubItem;

class Tag extends \Bitrix\Tasks\Item\Field\Collection\Item
{
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\Tag::getClass();
	}

	public function translateValueFromOutside($value, $key, $item)
	{
		if(is_string($value)) // it could be comma-separated tags
		{
			$value = array_map('trim', explode(',', $value));
		}

		if(is_array($value)) // it could be an array of strings
		{
			foreach($value as $k => $v)
			{
				if(is_string($v))
				{
					$v = array('NAME' => $v);
				}

				$value[$k] = $v;
			}
		}

		return parent::translateValueFromOutside($value, $key, $item);
	}

	/**
	 * Entity "tag" does not have single primary key, so different procedure here
	 *
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @return Result
	 * @throws ArgumentTypeException
	 */
	public function saveValueToDataBase($value, $key, $item)
	{
		$value = $this->translateValueToDatabase($value, $key, $item);

		$result = new Result();
		$errors = $result->getErrors();

		$itemId = $item->getId();
		/** @var SubItem $itemClass */
		$itemClass = static::getItemClass();
		$itemState = $item->getTransitionState();
		$isCreate = $itemState->isModeCreate();
		$isDelete = $itemState->isModeDelete();
		$newCodePattern = $this->getName().'.#CODE#';

		$this->onBeforeSaveToDataBase($value, $key, $item);

		$itemClass::enterBatchState();

		if(!$isCreate)
		{
			// delete all, because we do not have simple primary key to delete by
			$itemClass::deleteByParent($itemId);
		}

		if(!$isDelete)
		{
			// now add again...
			/** @var SubItem $subItem */
			foreach($value as $subItem)
			{
				// save each item of this collection separately
				$subItem->setParentId($itemId);
				$saveResult = $subItem->save(array(
					'KEEP_DATA' => true,
				));

				$errors->load($saveResult->getErrors()->transform(array('CODE' => $newCodePattern)));
			}
		}

		$itemClass::leaveBatchState();

		return $result;
	}
}