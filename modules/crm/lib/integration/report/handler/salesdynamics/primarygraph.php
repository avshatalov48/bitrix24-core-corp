<?php

namespace  Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;

class PrimaryGraph extends BaseGraph
{
	public function prepareQuery(Query $query)
	{
		parent::prepareQuery($query);
		$query->where("IS_RETURN_CUSTOMER", "N");
		$query->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);
		return $query;
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$params["IS_RETURN_CUSTOMER"] = "N";
		$params["STAGE_SEMANTIC_ID"] = PhaseSemantics::SUCCESS;
		return parent::getTargetUrl($baseUri, $params);
	}

	public function getMultipleGroupedData()
	{
		$result = parent::getMultipleGroupedData();
		foreach ($result["items"] as $k => $v)
		{
			$result["items"][$k]["balloon"]["amountInitial"] = $v["value"];
		}
		return $result;
	}
}