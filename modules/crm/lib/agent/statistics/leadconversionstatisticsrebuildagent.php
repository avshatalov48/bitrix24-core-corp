<?php
namespace Bitrix\Crm\Agent\Statistics;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Agent\EntityStepwiseAgent;

class LeadConversionStatisticsRebuildAgent extends EntityStepwiseAgent
{
	const ITERATION_LIMIT = 20;

	/** @var LeadConversionStatisticsRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return LeadConversionStatisticsRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadConversionStatisticsRebuildAgent();
		}
		return self::$instance;
	}
	public function getIterationLimit()
	{
		return (int)Option::get('crm', '~CRM_LEAD_CONVERSION_STATISTICS_REBUILD_STEP_LIMIT',  self::ITERATION_LIMIT);
	}
	protected function getOptionName()
	{
		return '~CRM_REBUILD_LEAD_CONVERSION_STATISTICS';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_REBUILD_LEAD_CONVERSION_STATISTICS_PROGRESS';
	}
	public function getTotalEntityCount()
	{
		return \CCrmLead::GetTotalCount();
	}
	public function getEntityIDs($offsetID, $limit)
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
	public function process(array $itemIDs)
	{
		\CCrmLead::RebuildStatistics(
			$itemIDs,
			array(
				'FORCED' => true,
				'ENABLE_HISTORY' => false,
				'ENABLE_SUM_STATISTICS' => false,
				'ENABLE_ACTIVITY_STATISTICS' => false,
				'ENABLE_CONVERSION_STATISTICS' => true
			)
		);
	}
}