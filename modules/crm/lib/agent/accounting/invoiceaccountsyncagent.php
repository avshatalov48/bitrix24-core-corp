<?php
namespace Bitrix\Crm\Agent\Accounting;

use Bitrix\Crm\Agent\EntityStepwiseAgent;

class InvoiceAccountSyncAgent extends EntityStepwiseAgent
{
	const ITERATION_LIMIT = 20;
	/** @var InvoiceAccountSyncAgent|null */
	private static $instance = null;
	/**
	 * @return InvoiceAccountSyncAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new InvoiceAccountSyncAgent();
		}
		return self::$instance;
	}
	//region EntityTimelineBuildAgent
	public function process(array $itemIDs)
	{
		\CCrmInvoice::RebuildStatistics(
			$itemIDs,
			array(
				'FORCED' => true,
				'ENABLE_SUM_STATISTICS' => true,
				'ENABLE_HISTORY'=> false
			)
		);
	}
	protected function getOptionName()
	{
		return '~CRM_SYNC_INVOICE_ACCOUNTING';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_SYNC_INVOICE_ACCOUNTING_PROGRESS';
	}
	protected function getTotalEntityCount()
	{
		return \CCrmInvoice::GetTotalCount();
	}
	protected function getEntityIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmInvoice::GetList(
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