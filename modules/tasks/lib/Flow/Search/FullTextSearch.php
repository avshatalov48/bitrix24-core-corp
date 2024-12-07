<?php

namespace Bitrix\Tasks\Flow\Search;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable;
use Bitrix\Main\ORM\Query\Filter;

class FullTextSearch
{
	public function index(Flow $flow): void
	{
		$index = (new IndexBuilder($flow))->build();
		FlowSearchIndexTable::set($flow->getId(), $index);
	}

	public function find(string $text): ?SqlExpression
	{
		$searchText = $this->prepareStringToSearch($text);
		if (empty($searchText))
		{
			return null;
		}

		$query = FlowSearchIndexTable::query()
			->setSelect(['FLOW_ID'])
			->whereMatch('SEARCH_INDEX', $searchText)
			->getQuery()
		;

		return new SqlExpression($query);
	}

	public function removeIndex(int $flowId): void
	{
		FlowSearchIndexTable::deleteByFilter([
			'FLOW_ID' => $flowId,
		]);
	}

	private function prepareStringToSearch(string $index): string
	{
		$index = trim($index);
		$index = mb_strtoupper($index);
		$index = $this->prepareToken($index);
		return Filter\Helper::matchAgainstWildcard($index);
	}

	private static function prepareToken(string $index): string
	{
		return str_rot13($index);
	}
}