<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global $USER_FIELD_MANAGER CUserTypeManager
 */


abstract class CTaskSubItemAbstract
{
	protected $oTaskItem         = null;
	protected $taskId            = null;
	protected $itemId            = null;
	protected $cachedData        = null;
	protected $cachedEscapedData = null;
	protected $executiveUserId   = null;


	public function __construct($oTaskItem, $itemId)
	{
		CTaskAssert::assert(
			($oTaskItem instanceof CTaskItemInterface)
			&& CTaskAssert::isLaxIntegers($itemId)
			&& ($itemId > 0)
		);

		$this->oTaskItem       = $oTaskItem;
		$this->itemId          = (int) $itemId;
		$this->taskId          = (int) $oTaskItem->getId();
		$this->executiveUserId = $oTaskItem->getExecutiveUserId();
	}


	final protected static function constructWithPreloadedData($oTaskItem, $itemId, $data)
	{
		/** @var $oItem CTaskSubItemAbstract */
		$oItem = new static($oTaskItem, $itemId);

		$oItem->cachedEscapedData = null;
		$oItem->cachedData = $data;

		return ($oItem);
	}


	public function getId()
	{
		return ($this->itemId);
	}


	public function getExecutiveUserId()
	{
		return ($this->executiveUserId);
	}


	public function getData($bEscape = true)
	{
		if ($this->cachedData === null)
		{
			try
			{
				// Ensure that we have read access for task
				$this->oTaskItem->getData();
				$this->cachedData = static::fetchDataFromDb($this->taskId, $this->itemId);
			}
			catch (Exception $e)
			{
				throw new TasksException('Check listitem not found or not accessible', TasksException::TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE);
			}
		}

		if ($bEscape)
		{
			// Prepare escaped data on-demand
			if ($this->cachedEscapedData === null)
			{
				foreach ($this->cachedData as $field => $value)
				{
					$this->cachedEscapedData['~' . $field] = $value;

					if (is_numeric($value) || ( ! is_string($value) ) )
						$this->cachedEscapedData[$field] = $value;
					else
						$this->cachedEscapedData[$field] = htmlspecialcharsex($value);
				}
			}

			return ($this->cachedEscapedData);
		}
		else
			return ($this->cachedData);
	}


	/**
	 * @param CTaskItemInterface $oTaskItem
	 * @throws TasksException
	 * @return array $arReturn with elements
	 * 		<ul>
	 * 		<li>$arReturn[0] - array of items
	 * 		<li>$arReturn[1] - CDBResult
	 * 		</ul>
	 */
	public static function fetchList(CTaskItemInterface $oTaskItem, $arOrder = array(), $arFilter = array())
	{
		$arItems = array();
		CTaskAssert::assert($oTaskItem instanceof CTaskItemInterface);

		try
		{
			// Ensure that we have read access for task
			$taskData = $oTaskItem->getData();
			list($arItemsData, $rsData) = static::fetchListFromDb($taskData, $arOrder, $arFilter);
		}
		catch (Exception $e)
		{
			throw new TasksException('Action failed', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		foreach ($arItemsData as $arItemData)
			$arItems[] = self::constructWithPreloadedData($oTaskItem, $arItemData['ID'], $arItemData);

		return (array($arItems, $rsData));
	}


	protected function resetCache()
	{
		$this->cachedData = null;
		$this->cachedEscapedData = null;
	}


	abstract protected static function fetchListFromDb($taskId, $arOrder);
	abstract protected static function fetchDataFromDb($taskId, $itemId);
}
