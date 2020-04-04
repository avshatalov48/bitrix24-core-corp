<?php
namespace Bitrix\Crm\Agent\Accounting;

use Bitrix\Crm\Agent\EntityStepwiseAgent;

class LeadAccountSyncAgent extends EntityStepwiseAgent
{
	const ITERATION_LIMIT = 20;
	/** @var LeadAccountSyncAgent|null */
	private static $instance = null;
	/**
	 * @return LeadAccountSyncAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadAccountSyncAgent();
		}
		return self::$instance;
	}
	//region EntityTimelineBuildAgent
	public function process(array $itemIDs)
	{
		\CCrmLead::RefreshAccountingData($itemIDs);
	}
	protected function getOptionName()
	{
		return '~CRM_SYNC_LEAD_ACCOUNTING';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_SYNC_LEAD_ACCOUNTING_PROGRESS';
	}
	protected function getTotalEntityCount()
	{
		return \CCrmLead::GetTotalCount();
	}
	protected function getEntityIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmLead::GetListEx(
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