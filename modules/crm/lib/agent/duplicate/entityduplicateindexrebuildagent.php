<?php
namespace Bitrix\Crm\Agent\Duplicate;

use Bitrix\Crm\Agent\AgentBase;

abstract class EntityDuplicateIndexRebuildAgent extends AgentBase
{
	const ITEM_LIMIT = 100;

	/**
	 * @return EntityDuplicateIndexRebuildAgent|null
	 */
	public static function getInstance()
	{
		return null;
	}

	public abstract function isActive();
	public abstract function isEnabled();
	public abstract function enable($enable);

	public abstract function getProgressData();
	public abstract function setProgressData(array $data);

	public abstract function getTotalCount();
	public abstract function prepareItemIDs($offsetID, $limit);
	public abstract function rebuild(array $itemIDs);

	public static function doRun()
	{
		$instance = static::getInstance();
		if($instance === null)
		{
			return false;
		}

		if(!$instance->isEnabled())
		{
			//Trace('Disabled', 'Y', 1);
			return false;
		}

		$progressData = $instance->getProgressData();

		$offsetID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = $instance->getTotalCount();
		}

		$itemIDs = $instance->prepareItemIDs($offsetID, self::ITEM_LIMIT);
		$itemQty = count($itemIDs);

		if($itemQty === 0)
		{
			$instance->enable(false);
			//Trace('Completed', $totalItemQty, 1);
			return false;
		}

		$instance->rebuild($itemIDs);

		$processedItemQty += $itemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['TOTAL_ITEMS'] = $totalItemQty;

		$instance->setProgressData($progressData);
		//Trace('Running', "{$processedItemQty} from {$totalItemQty}", 1);
		return true;
	}
}