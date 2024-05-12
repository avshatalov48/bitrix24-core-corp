<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class UserAttributesCondition implements Condition
{
	public function __construct(
		private ?array $categoryIds,
		private ?array $userIds,
		private ?array $progressSteps,
		private ?bool  $isOpened
	)
	{
	}

	public function getCategoryIds(): ?array
	{
		return $this->categoryIds;
	}

	public function getUserIds(): ?array
	{
		return $this->userIds;
	}

	public function getProgressSteps(): ?array
	{
		return $this->progressSteps;
	}

	public function getIsOpened(): ?bool
	{
		return $this->isOpened;
	}

	public function toArray() : array
	{
		return [
			'CATEGORY_ID' => $this->getCategoryIds(),
			'USER_ID' => $this->getUserIds(),
			'PROGRESS_STEP' => $this->getProgressSteps(),
			'IS_OPENED' => $this->getIsOpened(),
		];
	}


	public function toOrmCondition(bool $forJoin = false): ConditionTree
	{
		$px = $forJoin ? 'ref.' : '';

		$ct = new ConditionTree();

		if (!empty($this->getUserIds()))
		{
			$ct->whereIn($px.'USER_ID', $this->getUserIds());
		}

		if (!empty($this->getCategoryIds()))
		{
			$ct->whereIn($px.'CATEGORY_ID', $this->getCategoryIds());
		}

		if (!empty($this->getProgressSteps()))
		{
			$ct->whereIn($px.'PROGRESS_STEP', $this->getProgressSteps());
		}

		if ($this->getIsOpened() === true)
		{
			$ct->where($px.'IS_OPENED', 'Y');
		}

		return $ct;
	}
}