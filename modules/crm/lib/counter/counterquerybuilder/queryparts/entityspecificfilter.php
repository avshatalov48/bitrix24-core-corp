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
			: PhaseSemantics::PROCESS;

		if (!empty($stageSemanticId) && !is_array($stageSemanticId))
		{
			$stageSemanticId = [$stageSemanticId];
		}

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
			$stages = $this->queryQuoteProgressStatuses($stageSemanticId);
			$query->whereIn('STATUS_ID', $stages);
			if ($stageSemanticId === null)
			{
				$query->where('CLOSED', 'N');
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::SmartInvoice)
		{
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
			if ($factory->isStagesEnabled())
			{
				$statesIds = $this->queryDynamicEntityStages(null, $factory, $stageSemanticId);
				$query->whereIn('STAGE_ID', $statesIds);
			}
		}
		elseif(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeID))
		{
			$categoryId = $options['CATEGORY_ID'] ?? null;

			$factory = Container::getInstance()->getFactory($entityTypeID);
			if ($factory->isStagesEnabled())
			{
				$statesIds = $this->queryDynamicEntityStages($categoryId, $factory, $stageSemanticId);
				$query->whereIn('STAGE_ID', $statesIds);
			}

			if ($categoryId !== null)
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}
		}
	}

	private function queryQuoteProgressStatuses(array $stageSemanticIds): array
	{
		$subCt = new ConditionTree();
		if (empty($stageSemanticIds))
		{
			$subCt->logic(ConditionTree::LOGIC_OR);
			$subCt->whereNotIn('SEMANTICS', ['S', 'F']);
			$subCt->whereNull('SEMANTICS');
		}
		else
		{
			$subCt->whereIn('STATUS_ID', $stageSemanticIds);
		}

		$query = StatusTable::query()
			->setSelect(['STATUS_ID'])
			->where('ENTITY_ID', 'QUOTE_STATUS')
			->where($subCt)
			->setCacheTtl(120);

		return array_column($query->fetchAll(), 'STATUS_ID');
	}

	private function queryDynamicEntityStages(?int $categoryId, Factory $factory, ?array $stageSemanticIds): array
	{
		if ($categoryId !== null)
		{
			return $this->queryDynamicEntityStagesByCategory($categoryId, $factory, $stageSemanticIds);
		}

		$result = [];

		foreach ($factory->getCategories() as $category)
		{
			$result = array_merge(
				$result,
				$this->queryDynamicEntityStagesByCategory($category->getId(), $factory, $stageSemanticIds)
			);
		}
		return $result;
	}

	private function queryDynamicEntityStagesByCategory(
		int $categoryId,
		Factory $factory,
		?array $stageSemanticIds
	): array
	{
		$result = [];
		$stages = $factory->getStages($categoryId);

		$filterStagesBySemanticIds = function ($stage) use($stageSemanticIds) {
			$semantics = $stage->getSemantics() ?? PhaseSemantics::PROCESS;
			return in_array($semantics, $stageSemanticIds);
		};

		$filterStagesNotFinal = function ($stage) {
			return !PhaseSemantics::isFinal($stage->getSemantics());
		};

		$checkFn = $stageSemanticIds === null
			? $filterStagesNotFinal
			: $filterStagesBySemanticIds;

		foreach ($stages as $stage)
		{
			if ($checkFn($stage))
			{
				$result[] = $stage->getStatusId();
			}
		}

		return $result;
	}

}