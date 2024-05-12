<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class ObserversCondition implements Condition
{
	public function __construct(
		private int $entityTypeId,
		private int $userId,
		private ?array $categoryId,
	)
	{
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getCategoryId(): ?array
	{
		return $this->categoryId;
	}

	public function toArray(): array
	{
		return [
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'USER_ID' => $this->getUserId(),
			'CATEGORY_ID' => $this->getCategoryId(),
		];
	}

	public function toOrmCondition(bool $forJoin = false): ConditionTree
	{
		$px = $forJoin ? 'ref.' : '';

		$ct = new ConditionTree();
		$ct->whereIn($px.'CATEGORY_ID', $this->getCategoryId());

		$obsQuery = ObserverTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', $this->getEntityTypeId())
			->where('USER_ID', $this->getUserId());

		$ct->whereIn($px.'ENTITY_ID', $obsQuery);

		return $ct;
	}
}
