<?php
namespace Bitrix\Crm\Agent\Search;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Agent\AgentBase;

abstract class EntitySearchContentRebuildAgent extends AgentBase
{
	const ITEM_LIMIT = 50;

	/**
	 * @return EntitySearchContentRebuildAgent|null
	 */
	public static function getInstance()
	{
		return null;
	}

	public function isActive()
	{
		$dbResult = \CAgent::GetList(
			array('ID' => 'DESC'),
			array('NAME' => get_called_class().'::run(%')
		);
		return is_object($dbResult) && is_array($dbResult->Fetch());
	}

	public function activate($delay = 0)
	{
		if(!is_int($delay))
		{
			$delay = (int)$delay;
		}

		if($delay < 0)
		{
			$delay = 0;
		}

		\CAgent::AddAgent(
			get_called_class().'::run();',
			'crm',
			'N',
			0,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $delay, 'FULL')
		);
	}

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

		$limit = (int)Option::get('crm', '~CRM_SEARCH_CONTENT_STEP_LIMIT',  self::ITEM_LIMIT);
		if($limit <= 0)
		{
			$instance->enable(false);
			//Trace('Canceled', $totalItemQty, 1);
			return false;
		}

		$itemIDs = $instance->prepareItemIDs($offsetID, $limit);
		$itemQty = count($itemIDs);

		if($itemQty === 0)
		{
			$instance->enable(false);
			//Trace('Completed', $totalItemQty, 1);
			return false;
		}

		$instance->rebuild($itemIDs);

		$processedItemQty += $itemQty;
		if($totalItemQty < $processedItemQty)
		{
			$totalItemQty = $instance->getTotalCount();
		}

		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['TOTAL_ITEMS'] = $totalItemQty;

		$instance->setProgressData($progressData);
		//Trace('Running', "{$processedItemQty} from {$totalItemQty}", 1);
		return true;
	}
}