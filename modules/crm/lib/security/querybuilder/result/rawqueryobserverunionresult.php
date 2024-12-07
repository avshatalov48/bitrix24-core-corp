<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\Condition;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\ObserversCondition;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\UserAttributesCondition;
use Bitrix\Crm\Security\QueryBuilder\Result\Traits\UnionUtils;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Join;

/**
 * When forming security subqueries in some cases when selecting from ATTR tables, instead of using JOIN
 * with the observer table, it will be more productive to use UNION ALL to obtain a list of ENTITY_IDs
 * available to the user.
 * This class is an analogue of Bitrix\Crm\Security\QueryBuilder\Result\RawQueryResult
 * and if something could not be generated, it uses it to generate the result.
 *
 * result example:
 * ```sql
 * SELECT p.`ENTITY_ID` AS `ENTITY_ID`
 * FROM (SELECT o.`ENTITY_ID` AS `ENTITY_ID`
 * 		FROM `b_crm_observer` o
 * 		INNER JOIN `b_crm_access_attr_contact` oa ON o.`ENTITY_ID` = oa.`ENTITY_ID` AND oa.`CATEGORY_ID` IN (0)
 * 		WHERE o.`ENTITY_TYPE_ID` = 3 AND o.`USER_ID` = 3
 * 	   UNION ALL
 * 		SELECT `P`.`ENTITY_ID` AS `ENTITY_ID`
 * 		FROM `b_crm_access_attr_contact` `P`
 * 		WHERE (`P`.`USER_ID` IN (3, 7, 8) AND `P`.`CATEGORY_ID` IN (0))
 * ) p
 * ORDER BY `ENTITY_ID` ASC
 * LIMIT 0, 10;
 *```
 *
 * @link http://jabber.bx/view.php?id=181402
 */
final class RawQueryObserverUnionResult implements ResultOption
{
	use UnionUtils;
	public function __construct(
		private readonly ?string $order = null,
		private readonly ?int $limit = null,
		private readonly bool $useDistinct = false,
		private readonly string $identityColumnName = 'ID',
	)
	{
	}

	public function getIdentityColumnName(): string
	{
		return $this->identityColumnName;
	}

	public function getOrder(): ?string
	{
		return $this->order;
	}

	public function getLimit(): ?int
	{
		return $this->limit;
	}

	public function isUseDistinct(): bool
	{
		return $this->useDistinct;
	}

	public function make(Entity $entity, RestrictedConditionsList $conditions, string $prefix = ''): string
	{
		$sqlUnion = $this->createUnionSql($entity, $conditions);

		if (empty($sqlUnion))
		{
			return $this->createRawResult()->make($entity, $conditions, $prefix);
		}

		$virtualEntity = $this->makeVirtualEntity($sqlUnion);

		$query = new Query($virtualEntity);
		$query->setCustomBaseTableAlias($prefix . 'P');
		$query->setSelect(['ENTITY_ID']);

		if ($this->isUseDistinct())
		{
			$query->setDistinct();
		}

		if ($this->getLimit() > 0)
		{
			$order = $this->getOrder();
			$query->setOrder(['ENTITY_ID' => $order]);
			$query->setLimit($this->getLimit());
		}

		return $query->getQuery();
	}

	public function makeCompatible(string $querySql, string $prefix = ''): string
	{
		return $this->createRawResult()->makeCompatible($querySql, $prefix);
	}

	private function createUnionSql(Entity $entity, RestrictedConditionsList $conditions): string
	{
		[$otherConditions, $observerCondition] = $this->separateConditions($conditions->getConditions());

		if (empty($otherConditions) || empty($observerCondition)) {
			return '';
		}

		$mainQuery = $this->prepareAttrTableConditionWithoutObserver($entity, $otherConditions);

		$categoriesIds = $this->extractCategoryIdsFromConditions($conditions);
		$obsQuery = $this->prepareObserverQuery(
			$categoriesIds, $entity, $observerCondition);

		if ($this->getLimit() > 0)
		{
			$order = $this->getOrder();

			$mainQuery->setOrder(['ENTITY_ID' => $order]);
			$mainQuery->setLimit($this->getLimit());

			$obsQuery->setOrder(['ENTITY_ID' => $order]);
			$obsQuery->setLimit($this->getLimit());
		}

		return '(' . ($obsQuery->unionAll($mainQuery))->getQuery() . ')';
	}

	/**
	 * @param RestrictedConditionsList $conditions
	 * @return int[]
	 */
	private function extractCategoryIdsFromConditions(RestrictedConditionsList $conditions): array
	{
		/** @var UserAttributesCondition[] $userAttributesConditions */
		$userAttributesConditions = $this->filterConditionsByType(
			$conditions->getConditions(),
			UserAttributesCondition::class
		);

		$categoriesIds = [];
		foreach ($userAttributesConditions as $uac) {
			$categoriesIds = array_merge($categoriesIds, $uac->getCategoryIds());
		}

		return $categoriesIds;
	}

	/**
	 * @param Condition[] $conditions
	 */
	private function prepareAttrTableConditionWithoutObserver(Entity $entity, array $conditions): Query
	{
		$mainQuery = new Query($entity);
		$mainQuery->setSelect(['ENTITY_ID']);
		$mainQuery->where($this->makeOrmConditions($conditions));

		return $mainQuery;
	}

	private function createRawResult(): RawQueryResult
	{
		return new RawQueryResult(
			$this->getOrder(),
			$this->getLimit(),
			$this->isUseDistinct(),
			$this->getIdentityColumnName()
		);
	}

	/**
	 * @param int[] $categoriesIds
	 */
	private function prepareObserverQuery(array $categoriesIds, Entity $entity, ObserversCondition $observerCondition): Query
	{
		$joinAttrOnCondition = Join::on('this.ENTITY_ID', 'ref.ENTITY_ID');
		if (!empty($categoriesIds)) {
			$joinAttrOnCondition->whereIn('ref.CATEGORY_ID', $categoriesIds);
		}

		$obsQuery = ObserverTable::query()
			->setSelect(['ENTITY_ID'])
			->registerRuntimeField(new ReferenceField('entity_attr',
					$entity,
					$joinAttrOnCondition,
					['join_type' => 'INNER']
				)
			)
			->where('ENTITY_TYPE_ID', $observerCondition->getEntityTypeID())
			->where('USER_ID', $observerCondition->getUserId());
		return $obsQuery;
	}

	private function makeVirtualEntity(string $permsSqlWithUnion): Entity
	{
		static $cache = [];
		$hash = hash('crc32b', $permsSqlWithUnion);

		if (isset($cache[$hash]))
		{
			return $cache[$hash];
		}

		$entity = Entity::compileEntity(
			'VirtualPermsUnionWrapperEntity_' . $hash,
			[
				'ENTITY_ID' => ['data_type' => 'integer'],
			],
			[
				'table_name' => $permsSqlWithUnion,
				'namespace' => __NAMESPACE__,
			]
		);
		$cache[$hash] = $entity;

		return $entity;
	}

}