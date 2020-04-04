<?php
namespace Bitrix\Crm\Agent\Semantics;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Agent\EntityStepwiseAgent;

class LeadSemanticsRebuildAgent extends EntityStepwiseAgent
{
	const ITERATION_LIMIT = 20;

	/** @var LeadSemanticsRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return LeadSemanticsRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadSemanticsRebuildAgent();
		}
		return self::$instance;
	}
	public function getIterationLimit()
	{
		return (int)Option::get('crm', '~CRM_SEMANTIC_REBUILD_STEP_LIMIT',  self::ITERATION_LIMIT);
	}
	protected function getOptionName()
	{
		return '~CRM_REBUILD_LEAD_SEMANTICS';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_REBUILD_LEAD_SEMANTICS_PROGRESS';
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
		\CCrmLead::RebuildSemantics($itemIDs, array('FORCED' => true));
	}
}