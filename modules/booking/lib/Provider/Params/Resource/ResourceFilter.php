<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Resource;

use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class ResourceFilter extends Filter
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

		if (isset($this->filter['EXTERNAL_ID']))
		{
			if (is_array($this->filter['EXTERNAL_ID']))
			{
				$result->whereIn('EXTERNAL_ID', array_map('intval', $this->filter['EXTERNAL_ID']));
			}
			else
			{
				$result->where('EXTERNAL_ID', '=', (int)$this->filter['EXTERNAL_ID']);
			}
		}

		if (isset($this->filter['IS_MAIN']))
		{
			$result->where('IS_MAIN', '=', (bool)$this->filter['IS_MAIN']);
		}

		if (isset($this->filter['TYPE_ID']))
		{
			if (is_array($this->filter['TYPE_ID']))
			{
				$result->whereIn('TYPE_ID', array_map('intval', $this->filter['TYPE_ID']));
			}
			else
			{
				$result->where('TYPE_ID', '=', (int)$this->filter['TYPE_ID']);
			}
		}

		if (isset($this->filter['NAME']))
		{
			$result->where('DATA.NAME', '=', (string)$this->filter['NAME']);
		}

		if (isset($this->filter['SEARCH_QUERY']))
		{
			$result->whereLike('DATA.NAME', '%' . $this->filter['SEARCH_QUERY'] . '%');
		}

		if (isset($this->filter['DESCRIPTION']))
		{
			$result->where('DATA.DESCRIPTION', '=', (string)$this->filter['DESCRIPTION']);
		}

		return $result;
	}
}
