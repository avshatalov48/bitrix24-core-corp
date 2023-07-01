<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;

final class SelectFields
{
	public function applyCompatible(Query $query, QueryParams $params)
	{
		// @ToDo
	}

	public function applyForUncompleted(Query $query, QueryParams $params)
	{
		if ($params->getSelectType() === CounterQueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
			$query->addSelect('QTY');
		}
	}

	public function applyForCountable(Query $query, QueryParams $params): Query
	{
		if($params->getSelectType() === CounterQueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
			if($params->useDistinct())
			{
				$query->addGroup('ID');
			}
		}
		else
		{
			if ($params->counterLimit())
			{
				$query->setLimit($params->counterLimit());
				$query->addSelect('ID');

				$entity = Entity::getInstanceByQuery($query);

				$newQuery = (new Query($entity));
				$newQuery->registerRuntimeField('', self::getQuantityExpression($params->useDistinct()));
				$newQuery->addSelect('QTY');

				return $newQuery;
			}
			else
			{
				$query->registerRuntimeField('', self::getQuantityExpression($params->useDistinct()));
				$query->addSelect('QTY');
			}
		}
		return $query;
	}

	public static function getQuantityExpression(bool $useDistinct): ExpressionField
	{
		if ($useDistinct)
		{
			return new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID');
		}

		return new ExpressionField('QTY', 'COUNT(%s)', 'ID');
	}
}
