<?php
namespace Bitrix\Crm\Agent;

use Bitrix\Crm;

abstract class LeadStepwiseAgent extends Crm\Agent\EntityStepwiseAgent
{
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
}