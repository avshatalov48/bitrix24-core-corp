<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\ResourceType;

use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class ResourceTypeFilter extends Filter
{
	private array $filter;

	public function __construct(array $filter = [])
	{
		$this->filter = $filter;
	}

	public function prepareFilter(): ConditionTree
	{
		$result = new ConditionTree();

		if (isset($this->filter['ID']))
		{
			if (is_array($this->filter['ID']))
			{
				$result->whereIn('ID', array_map('intval', $this->filter['ID']));
			}
			else
			{
				$result->where('ID', '=', (int)$this->filter['ID']);
			}
		}

		if (isset($this->filter['MODULE_ID']))
		{
			$result->where('MODULE_ID', '=', (string)$this->filter['MODULE_ID']);
		}

		if (isset($this->filter['CODE']))
		{
			if (is_array($this->filter['CODE']))
			{
				$result->whereIn('CODE', $this->filter['CODE']);
			}
			else
			{
				$result->where('CODE', '=', (string)$this->filter['CODE']);
			}
		}

		if (isset($this->filter['NAME']))
		{
			$result->where('NAME', '=', (string)$this->filter['NAME']);
		}

		if (isset($this->filter['SEARCH_QUERY']))
		{
			$result->whereLike('NAME', '%' . $this->filter['SEARCH_QUERY'] . '%');
		}

		return $result;
	}
}
