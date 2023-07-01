<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ORM\Query\Query;

final class ResponsibleFilter
{
	public function applyByItemFactory(Query $query, QueryParams $params, Factory $factory): void
	{
		$responsibleFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		$this->apply($query, $params, $responsibleFieldName);
	}

	public function apply(Query $query, QueryParams $params, string $responsibleFieldName): void
	{
		if (empty($params->userIds()))
		{
			return;
		}

		if ($params->excludeUsers())
		{
			if (count($params->userIds()) > 1)
			{
				$query->whereNotIn($responsibleFieldName, array_merge($params->userIds(), [0]));
			}
			else
			{
				$query->whereNot($responsibleFieldName, $params->userIds()[0]);
				$query->whereNot($responsibleFieldName, 0);
			}
		}
		else
		{
			if (count($params->userIds()) > 1)
			{
				$query->whereIn($responsibleFieldName, $params->userIds());
			}
			else
			{
				$query->where($responsibleFieldName, $params->userIds()[0]);
			}
		}
	}

}