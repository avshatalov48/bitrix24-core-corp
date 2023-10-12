<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams;

final class UserParams
{
	/**
	 * @var int[]
	 */
	private array $userIds;

	private bool $isExcluded;

	public function __construct(array $userIds, bool $isExcluded)
	{
		$this->userIds = array_values(array_unique(array_map('intval', $userIds)));;
		$this->isExcluded = $isExcluded;
	}

	/**
	 * @return int[]
	 */
	public function userIds(): array
	{
		return $this->userIds;
	}

	public function isExcluded(): bool
	{
		return $this->isExcluded;
	}

	public function isOnlyOneUser(): bool
	{
		if ($this->isExcluded())
		{
			return false;
		}
		return count($this->userIds()) === 1;
	}

	public function firstUserId(): ?int
	{
		return $this->userIds()[0] ?? null;
	}

}