<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class AlwaysReadableCondition implements Condition
{

	public function __construct(private array $categoryIds)
	{
		if (empty($categoryIds))
		{
			throw new ArgumentException('Categories must not be empty');
		}
	}

	public function getCategoryIds(): array
	{
		return $this->categoryIds;
	}

	public function toOrmCondition(bool $forJoin = false): ConditionTree
	{
		$px = $forJoin ? 'ref.' : '';

		$ct = new ConditionTree();
		$ct->whereIn($px.'CATEGORY_ID', $this->getCategoryIds());
		$ct->where($px.'IS_ALWAYS_READABLE', 'Y');

		return $ct;
	}

	public function toArray(): array
	{
		return [
			'CATEGORY_ID' => $this->getCategoryIds(),
			'IS_ALWAYS_READABLE' => true,
		];
	}

}