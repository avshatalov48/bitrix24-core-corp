<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

final class EntitySpecificFilter
{
	public function apply(Query $query, int $entityTypeID, array $options)
	{
		$stageSemanticId = isset($options['STAGE_SEMANTIC_ID']) && $options['STAGE_SEMANTIC_ID']
			? $options['STAGE_SEMANTIC_ID']
			: PhaseSemantics::PROCESS
		;
		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			$query->addFilter('=STAGE_SEMANTIC_ID', $stageSemanticId);
			$query->addFilter('=IS_RECURRING', 'N');

			if(isset($options['CATEGORY_ID']) && $options['CATEGORY_ID'] >= 0)
			{
				$query->addFilter('=CATEGORY_ID', new SqlExpression('?i', $options['CATEGORY_ID']));
			}
		}
		else if($entityTypeID === \CCrmOwnerType::Contact)
		{
			if (isset($options['CATEGORY_ID']))
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}
		}
		else if($entityTypeID === \CCrmOwnerType::Company)
		{
			$query->addFilter('=IS_MY_COMPANY', 'N');
			if (isset($options['CATEGORY_ID']))
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			$query->addFilter('=CANCELED', 'N');
			$query->addFilter('@STATUS_ID', OrderStatus::getSemanticProcessStatuses());
		}
		elseif($entityTypeID === \CCrmOwnerType::Lead)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $stageSemanticId);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			$query->where('CLOSED', 'N');
			$query->whereIn('STATUS_ID', $this->queryQuoteProgressStatuses());
		}
		elseif($entityTypeID === \CCrmOwnerType::SmartInvoice)
		{
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
			if ($factory->isStagesEnabled())
			{
				$statesIds = $this->queryDynamicEntityStages(null, $factory);
				$query->whereIn('STAGE_ID', $statesIds);
			}
		}
		elseif(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeID))
		{
			$categoryId = $options['CATEGORY_ID'] ?? null;

			$factory = Container::getInstance()->getFactory($entityTypeID);
			if ($factory->isStagesEnabled())
			{
				$statesIds = $this->queryDynamicEntityStages($categoryId, $factory);
				$query->whereIn('STAGE_ID', $statesIds);
			}

			if ($categoryId !== null)
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}
		}
	}

	private function queryQuoteProgressStatuses(): array
	{
		$subCt = new ConditionTree();
		$subCt->logic(ConditionTree::LOGIC_OR);
		$subCt->whereNotIn('SEMANTICS', ['S', 'F']);
		$subCt->whereNull('SEMANTICS');


		$query = StatusTable::query()
			->setSelect(['STATUS_ID'])
			->where('ENTITY_ID', 'QUOTE_STATUS')
			->where($subCt)
			->setCacheTtl(120);

		return array_column($query->fetchAll(), 'STATUS_ID');
	}

	private function queryDynamicEntityStages(?int $categoryId, Factory $factory): array
	{
		if ($categoryId !== null)
		{
			return $this->queryDynamicEntityStagesByCategory($categoryId, $factory);
		}

		$result = [];

		foreach ($factory->getCategories() as $category)
		{
			$result = array_merge(
				$result,
				$this->queryDynamicEntityStagesByCategory($category->getId(), $factory)
			);
		}
		return $result;
	}

	private function queryDynamicEntityStagesByCategory(int $categoryId, Factory $factory): array
	{
		$result = [];
		$stages = $factory->getStages($categoryId);
		foreach ($stages as $stage)
		{
			if (!PhaseSemantics::isFinal($stage->getSemantics()))
			{
				$result[] = $stage->getStatusId();
			}
		}
		return $result;
	}

}