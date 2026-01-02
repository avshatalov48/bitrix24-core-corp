<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator;

use Bitrix\Main\ORM\Query\Query;

class Executor
{
	public function __construct(
		/** @var AbstractQueryModificator[] */
		private readonly array $queryModificators,
	)
	{
	}

	public function execute(Query $query): ExecutorResultValues
	{
		if (empty($this->queryModificators))
		{
			return new ExecutorResultValues();
		}

		foreach ($this->queryModificators as $queryModificator)
		{
			$queryModificator->modify($query);
		}

		$fetchAllResult = $query->fetchAll();

		$result = new ExecutorResultValues();
		foreach ($this->queryModificators as $queryModificator)
		{
			$queryModificator->transferFormattedValues($fetchAllResult, $result);
			$queryModificator->fillDefaultValue($result);
		}

		return $result;
	}
}
