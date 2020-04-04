<?php
namespace Bitrix\Crm\Agent\Accounting;

use Bitrix\Crm\Agent\EntityStepwiseAgent;

class OrderAccountSyncAgent extends EntityStepwiseAgent
{
	const ITERATION_LIMIT = 20;
	/** @var DealAccountSyncAgent|null */
	private static $instance = null;
	/**
	 * @return DealAccountSyncAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderAccountSyncAgent();
		}
		return self::$instance;
	}
	//region EntityTimelineBuildAgent
	public function process(array $itemIDs)
	{
//		OrderEntity::refreshAccountingData($itemIDs);
		return;
	}
	protected function getOptionName()
	{
		return '~CRM_SYNC_ORDER_ACCOUNTING';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_SYNC_ORDER_ACCOUNTING_PROGRESS';
	}
	// todo: order
	protected function getTotalEntityCount()
	{
		return \CCrmDeal::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	// todo: order
	protected function getEntityIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmDeal::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => $limit),
			array('ID')
		);

		$results = array();

		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[] = (int)$fields['ID'];
			}
		}
		return $results;
	}
	protected function getIterationLimit()
	{
		return self::ITERATION_LIMIT;
	}
	//endregion
}