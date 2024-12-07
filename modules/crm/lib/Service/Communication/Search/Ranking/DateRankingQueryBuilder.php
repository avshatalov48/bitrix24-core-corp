<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use CCrmOwnerType;

final class DateRankingQueryBuilder
{
	public function __construct(
		private readonly array $searchEntityTypeIds,
		private readonly array $duplicates,
		private readonly array $order
	)
	{

	}

	public function build(int $entityTypeId, array $params = []): ?Query
	{
		if ($entityTypeId === CCrmOwnerType::Lead)
		{
			if ($params['returnCustomerLead'] ?? null === true)
			{
				return $this->buildQueryReturnCustomerLead();
			}

			return $this->buildQueryLead();
		}

		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			return $this->buildQueryDeal();
		}

		if ($entityTypeId === CCrmOwnerType::Contact)
		{
			return $this->buildQueryContact();
		}

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			return $this->buildQueryCompany();
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return $this->buildQueryDynamic($entityTypeId);
		}

		return null;
	}

	private function buildQueryLead(): ?Query
	{
		$query = LeadTable::query()
			->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS)
			->addFilter('=IS_RETURN_CUSTOMER', 'N')
		;

		return $this->getPreparedQuery($query, CCrmOwnerType::Lead);
	}

	private function buildQueryReturnCustomerLead(): ?Query
	{
		$query = LeadTable::query()
			->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS)
			->addFilter('=IS_RETURN_CUSTOMER', 'Y')
		;

		return $this->getPreparedQuery($query, CCrmOwnerType::Lead);
	}

	private function buildQueryDeal(): ?Query
	{
		$query = DealTable::query()
			->addFilter('=STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS)
			->addFilter('=IS_RECURRING', 'N')
		;

		return $this->getPreparedQuery($query, CCrmOwnerType::Deal);
	}

	private function buildQueryContact(): Query
	{
		$query = ContactTable::query()
			->setSelect(['MAX_ID'])
			->whereIn('ID', $this->duplicates[CCrmOwnerType::Contact])
			->setOrder($this->order)
			->setLimit(1)
		;

		$this->registerRuntimeFields($query, \CCrmOwnerType::Contact);

		return $query;
	}

	private function buildQueryCompany(): Query
	{
		$query = CompanyTable::query()
			->setSelect([
				'MAX_ID',
			])
			->whereIn('ID', $this->duplicates[CCrmOwnerType::Company])
			->setOrder($this->order)
			->setLimit(1)
		;

		$this->registerRuntimeFields($query, \CCrmOwnerType::Company);

		return $query;
	}

	private function buildQueryDynamic(int $rankedEntityTypeId): ?Query
	{
		$factory = Container::getInstance()->getFactory($rankedEntityTypeId);
		if ($factory === null)
		{
			return null;
		}

		$query = $factory->getDataClass()::query()
			->addFilter('!=STAGE.SEMANTICS', PhaseSemantics::getFinalSemantis())
		;

		return $this->getPreparedQuery($query, $rankedEntityTypeId);
	}

	private function getPreparedQuery(Query $query, int $rankEntityTypeId): ?Query
	{
		$query = clone $query;

		$select = ['MAX_ID'];
		$conditionTree = new ConditionTree();
		$conditionTree->logic(ConditionTree::LOGIC_OR);

		$emptySearchedEntities = true;
		foreach ($this->searchEntityTypeIds as $searchEntityTypeId)
		{
			if (empty($this->duplicates[$searchEntityTypeId]))
			{
				continue;
			}

			$emptySearchedEntities = false;

			switch ($searchEntityTypeId)
			{
				case CCrmOwnerType::Contact:
					[$fieldName, $filterFieldName] = $this->getContactFieldNames($rankEntityTypeId);
					break;
				case CCrmOwnerType::Company:
					[$fieldName, $filterFieldName] = $this->getCompanyFieldNames();
					break;
				case CCrmOwnerType::Lead:
					[$fieldName, $filterFieldName] = $this->getLeadFieldNames();
					break;
				default:
					return null;
			}

			$select[$fieldName] = $filterFieldName;

			$conditionTree->addCondition(
				(new ConditionTree())->whereIn($filterFieldName, $this->duplicates[$searchEntityTypeId])
			);
		}

		if ($emptySearchedEntities)
		{
			return null;
		}

		$limit = 1;

		$query
			->setSelect($select)
			->where($conditionTree)
			->setLimit($limit)
			->setOrder($this->order)
		;

		$this->registerRuntimeFields($query, $rankEntityTypeId);

		return $query;
	}

	private function getContactFieldNames(int $rankEntityTypeId): array
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($rankEntityTypeId))
		{
			return ['CONTACTS', 'CONTACT_BINDINGS.CONTACT_ID'];
		}

		return ['BINDING_CONTACT_ID', 'BINDING_CONTACT.CONTACT_ID'];
	}

	private function getCompanyFieldNames(): array
	{
		return ['COMPANY_ID', 'COMPANY_ID'];
	}

	private function getLeadFieldNames(): array
	{
		return ['ID', 'ID'];
	}

	private function registerRuntimeFields(Query $query, int $rankEntityTypeId): void
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($rankEntityTypeId))
		{
			$dateCreateFieldName = 'CREATED_TIME';
			$dateModifyFieldName = 'UPDATED_TIME';
		}
		else
		{
			$dateCreateFieldName = 'DATE_CREATE';
			$dateModifyFieldName = 'DATE_MODIFY';
		}

		$query
			->registerRuntimeField(new ExpressionField('MAX_DATE_CREATE', 'MAX(%s)', $dateCreateFieldName))
			->registerRuntimeField(new ExpressionField('MAX_DATE_MODIFY', 'MAX(%s)', $dateModifyFieldName))
			->registerRuntimeField(new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'))
		;
	}
}
