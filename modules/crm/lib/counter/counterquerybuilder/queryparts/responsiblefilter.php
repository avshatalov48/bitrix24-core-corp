<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\UserParams;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

final class ResponsibleFilter
{
	public function applyByItemFactory(Query $query, UserParams $userParams, Factory $factory): void
	{
		$responsibleFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		$this->apply($query, $userParams, $responsibleFieldName);
	}

	/**
	 * @param Query|ConditionTree $query
	 * @param UserParams $userParams
	 * @param string $responsibleFieldName
	 * @return void
	 */
	public function apply($query, UserParams $userParams, string $responsibleFieldName): void
	{
		if (empty($userParams->userIds()))
		{
			return;
		}

		$query->where($this->prepareConditions($userParams, $responsibleFieldName));
	}

	private function prepareConditions(UserParams $userParams, string $responsibleFieldName): ConditionTree
	{
		$ct = new ConditionTree();

		if ($userParams->isExcluded())
		{
			if (count($userParams->userIds()) > 1)
			{
				$ct->whereNotIn($responsibleFieldName, array_merge($userParams->userIds(), [0]));
			}
			else
			{
				$ct->whereNot($responsibleFieldName, $userParams->userIds()[0]);
				$ct->whereNot($responsibleFieldName, 0);
			}
		}
		else
		{
			if (count($userParams->userIds()) > 1)
			{
				$ct->whereIn($responsibleFieldName, $userParams->userIds());
			}
			else
			{
				$ct->where($responsibleFieldName, $userParams->userIds()[0]);
			}
		}

		return $ct;
	}


}