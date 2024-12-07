<?php

namespace Bitrix\Crm\AutomatedSolution\Action\Read;

use Bitrix\Crm\AutomatedSolution\Action\Action;
use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;

final class Fetch implements Action
{
	public function __construct(
		private readonly ?array $filter = null,
		private readonly ?array $order = null,
		private readonly ?int $offset = null,
		private readonly ?int $limit = null,
	)
	{
	}

	public function execute(): Result
	{
		$automatedSolutions = [];

		$dbResult = $this->prepareQuery()->exec();
		while ($row = $dbResult->fetchObject())
		{
			$automatedSolutions[] = $row->collectValues();
		}

		$enrichedAutomatedSolutions = $this->enrichWithTypeIds($automatedSolutions);

		return (new Result())->setData(['automatedSolutions' => $enrichedAutomatedSolutions]);
	}

	/**
	 * @return \Bitrix\Crm\AutomatedSolution\Entity\EO_AutomatedSolution_Query
	 */
	private function prepareQuery(): Query
	{
		$query = AutomatedSolutionTable::query();

		$query
			->setSelect([
				'ID',
				'INTRANET_CUSTOM_SECTION_ID',
				'TITLE',
				'CODE',
			])
		;

		if ($this->filter !== null)
		{
			$query->setFilter($this->filter);
		}

		if ($this->order !== null)
		{
			$query->setOrder($this->order);
		}

		if ($this->offset !== null)
		{
			$query->setOffset($this->offset);
		}

		if ($this->limit !== null)
		{
			$query->setLimit($this->limit);
		}

		return $query;
	}

	private function enrichWithTypeIds(array $automatedSolutions): array
	{
		if (empty($automatedSolutions))
		{
			return $automatedSolutions;
		}

		$map = $this->getAutomatedSolutionIdToTypeIdsMap(array_column($automatedSolutions, 'ID'));
		foreach ($automatedSolutions as &$solution)
		{
			$typeIds = $map[$solution['ID']] ?? [];
			$solution['TYPE_IDS'] = $typeIds;
		}

		return $automatedSolutions;
	}

	private function getAutomatedSolutionIdToTypeIdsMap(array $automatedSolutionIds): array
	{
		$result = (new FetchBoundTypeIds($automatedSolutionIds))->execute();

		return $result->getData()['typeIdsMap'] ?? [];
	}
}
