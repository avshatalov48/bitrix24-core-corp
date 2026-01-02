<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\WaitListItem;

use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

class WaitListItemFilter implements FilterInterface
{
	private array $filter;

	public function __construct(array $filter = [])
	{
		$this->filter = $filter;
	}

	public function prepareFilter(): ConditionTree
	{
		$result = new ConditionTree();

		$includeDeleted = (
			isset($this->filter['INCLUDE_DELETED'])
			&& $this->filter['INCLUDE_DELETED'] === true
		);
		if (!$includeDeleted)
		{
			$result->where('IS_DELETED', '=', 'N');
		}

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

		if (isset($this->filter['CREATED_BY']))
		{
			if (is_array($this->filter['CREATED_BY']))
			{
				$result->whereIn('CREATED_BY', array_map('intval', $this->filter['CREATED_BY']));
			}
			else
			{
				$result->where('CREATED_BY', '=', (int)$this->filter['CREATED_BY']);
			}
		}

		if (
			isset($this->filter['CREATED_WITHIN']['FROM'])
			&& isset($this->filter['CREATED_WITHIN']['TO'])
		)
		{
			$result
				->where('CREATED_AT', '>=', $this->filter['CREATED_WITHIN']['FROM'])
				->where('CREATED_AT', '<', $this->filter['CREATED_WITHIN']['TO'])
			;
		}

		return $result;
	}

	public function prepareQuery(Query $query): void
	{
		// TODO: Implement prepareQuery() method.
	}
}
