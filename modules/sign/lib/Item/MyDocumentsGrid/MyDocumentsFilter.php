<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;

class MyDocumentsFilter
{
	/**
	 * @param ActorRole|null $role
	 * @param list<int> $initiators
	 * @param list<int> $editors
	 * @param list<int> $reviewers
	 * @param list<int> $signers
	 * @param list<int> $assignees
	 * @param list<int> $companies
	 * @param list<FilterStatus> $statuses
	 * @param DateTime|null $dateModifyFrom
	 * @param DateTime|null $dateModifyTo
	 * @param string $text
	 * @param list<int> $assigneesOrSigners
	 */
	public function __construct(
		public readonly ?ActorRole $role = null,
		public readonly array $initiators = [],
		public readonly array $editors = [],
		public readonly array $reviewers = [],
		public readonly array $signers = [],
		public readonly array $assignees = [],
		public readonly array $companies = [],
		public readonly array $statuses = [],
		public readonly ?DateTime $dateModifyFrom = null,
		public readonly ?DateTime $dateModifyTo = null,
		public readonly string $text = '',
		public readonly array $assigneesOrSigners = [],
	)
	{}

	public function isEmpty(): bool
	{
		return $this->role === null
			&& empty($this->initiators)
			&& empty($this->editors)
			&& empty($this->reviewers)
			&& empty($this->signers)
			&& empty($this->assignees)
			&& empty($this->companies)
			&& empty($this->statuses)
			&& empty($this->dateModifyFrom)
			&& empty($this->dateModifyTo)
			&& empty($this->text)
			&& empty($this->assigneesOrSigners)
		;
	}

	public function isNeedActionFeatureSort(): bool
	{
		foreach ($this->statuses as $status)
		{
			if ($status === FilterStatus::NEED_ACTION)
			{
				// we dont need slow sort if only status need action visible
				return count($this->statuses) > 1;
			}

			if ($status === FilterStatus::IN_PROGRESS)
			{
				return true;
			}
		}

		return false;
	}

	public function isFilterOnlyNeedAction(): bool
	{
		return $this->role === null
			&& empty($this->initiators)
			&& empty($this->editors)
			&& empty($this->reviewers)
			&& empty($this->signers)
			&& empty($this->assignees)
			&& empty($this->companies)
			&& empty($this->dateModifyFrom)
			&& empty($this->dateModifyTo)
			&& empty($this->text)
			&& empty($this->assigneesOrSigners)
			&& count($this->statuses) === 1
			&& ($this->statuses[0] ?? null) === FilterStatus::NEED_ACTION
			;
	}

}