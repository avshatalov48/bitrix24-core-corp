<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

final class ItemListQueries
{
	use Singleton;

	public function queryTimelineIdsByFilter(ListParams\Params $listParams): array
	{
		$query = $this->makeTimelineQuery($listParams);

		$query
			->setSelect(['ID'])
			->setOrder($listParams->getOrder())
			->setLimit($listParams->getLimit())
			->setOffset($listParams->getOffset());

		return array_column($query->fetchAll(), 'ID');
	}

	public function totalCount(ListParams\Params $listParams): ?int
	{
		$query = $this->makeTimelineQuery($listParams);
		$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
		$query->addSelect('QTY');
		return $query->fetch()['QTY'] ?? 0;
	}

	private function makeTimelineQuery(ListParams\Params $listParams): Query
	{
		$bindingsSubQuery = null;
		if ($listParams->getFilter()->hasBindingsFilter())
		{
			$ct = new ConditionTree();
			$ct->logic(ConditionTree::LOGIC_OR);

			foreach ($listParams->getFilter()->getBindingsFilter() as $binding)
			{
				$subCt = new ConditionTree();
				$subCt->where('ENTITY_ID', (int)$binding['ENTITY_ID']);
				$subCt->where('ENTITY_TYPE_ID', (int)$binding['ENTITY_TYPE_ID']);

				$ct->where($subCt);
			}

			$bindingsSubQuery = TimelineBindingTable::query()
				->setSelect(['OWNER_ID'])
				->where($ct);
		}

		$query = TimelineTable::query()
			->setFilter($listParams->getFilter()->getOnlyTimelineTableFilterFields());

		if ($bindingsSubQuery !== null)
		{
			$query->whereIn('ID', $bindingsSubQuery);
		}

		return $query;
	}

	public function queryTimelineWithBindingsByIds(array $timelineIds, array $order): array
	{
		$items = TimelineTable::query()
			->setSelect(['*'])
			->whereIn('ID', $timelineIds)
			->setOrder($order)
			->fetchAll();

		$bindingsQuery = TimelineBindingTable::query()
			->setSelect(['OWNER_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'])
			->whereIn('OWNER_ID', $timelineIds);

		$bindings = [];
		foreach ($bindingsQuery->fetchAll() as $binding)
		{
			$timelineId = $binding['OWNER_ID'];
			if (!isset($bindings[$timelineId]))
			{
				$bindings[$timelineId] = [];
			}
			$bindings[$timelineId][] = [
				'ENTITY_TYPE_ID' => (int)$binding['ENTITY_TYPE_ID'],
				'ENTITY_ID' => (int)$binding['ENTITY_ID'],
			];
		}

		return array_map(function ($item) use ($bindings) {
			$itemBindings = $bindings[$item['ID']] ?? [];
			return array_merge($item, ['BINDINGS' => $itemBindings]);
		}, $items);
	}
}