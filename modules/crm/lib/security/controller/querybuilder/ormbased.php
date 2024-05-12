<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\Controller;
use Bitrix\Crm\Security\Controller\QueryBuilder;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\ConditionBuilder;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;

class OrmBased extends QueryBuilder
{

	private ConditionBuilder $conditionBuilder;

	public function __construct(
		private Controller\Base $controller
	)
	{
		$this->conditionBuilder = new ConditionBuilder($this->controller);
	}

	public function build(Collection $attributes, QueryBuilderOptions $options): QueryBuilderData
	{
		$prefix = $options->getAliasPrefix();

		$conditions = $this->conditionBuilder->build($options, $attributes);

		$entity = $this->controller->getEntity();

		if ($conditions->isEmpty())
		{
			return new QueryBuilderData('', $conditions, $entity);
		}

		$sql = $options->getResult()->make($entity, $conditions, $prefix);

		return new QueryBuilderData($sql, $conditions, $entity);
	}
}
