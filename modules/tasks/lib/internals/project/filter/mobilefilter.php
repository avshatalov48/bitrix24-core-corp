<?php

namespace Bitrix\Tasks\Internals\Project\Filter;

use Bitrix\Main\Entity\Query;
use Bitrix\Tasks\Internals\Project\Filter;

class MobileFilter extends Filter
{
	public function process(Query $query, array $filter): Query
	{
		if (array_key_exists('ID', $filter))
		{
			$ids = (is_array($filter['ID']) ? $filter['ID'] : [$filter['ID']]);
			$ids = array_map('intval', $ids);
			$ids = array_filter($ids);

			if (!empty($ids))
			{
				count($ids) > 1
					? $query->whereIn('ID', $ids)
					: $query->where('ID', $ids[0])
				;
			}
		}

		if (array_key_exists('SEARCH_INDEX', $filter) && trim($filter['SEARCH_INDEX']) !== '')
		{
			$query = $this->processFilterSearch($query, $filter['SEARCH_INDEX']);
		}

		if (array_key_exists('MEMBER_ID', $filter))
		{
			$query = $this->processFilterMember($query, $filter['MEMBER_ID']);
		}

		if (array_key_exists('COUNTERS', $filter))
		{
			$query = $this->processFilterCounters($query, $filter['COUNTERS']);
		}

		return $query;
	}
}
