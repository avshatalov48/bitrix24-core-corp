<?php

namespace Bitrix\Crm\Filter\Activity;

final class PrepareResult
{
	private array $filter;

	private ?array $counterUserIds;

	private ?bool $excludeUsers;

	private ?int $counterTypeId;

	private bool $isApplyCounterFilter;

	public function __construct(
		array $filter,
		?array $counterUserIds = null,
		?bool $excludeUsers = null,
		?int $counterTypeId = null,
		?bool $isApplyCounterFilter = false
	)
	{
		$this->filter = $filter;
		$this->counterUserIds = $counterUserIds;
		$this->excludeUsers = $excludeUsers;
		$this->counterTypeId = $counterTypeId;
		$this->isApplyCounterFilter = $isApplyCounterFilter;
	}

	/**
	 * @return array
	 */
	public function filter(): array
	{
		return $this->filter;
	}

	/**
	 * @return array
	 */
	public function counterUserIds(): ?array
	{
		return $this->counterUserIds;
	}

	/**
	 * @return bool
	 */
	public function isExcludeUsers(): ?bool
	{
		return $this->excludeUsers;
	}

	/**
	 * @return bool
	 */
	public function willApplyCounterFilter(): bool
	{
		return $this->isApplyCounterFilter;
	}

	/**
	 * @return int|null
	 */
	public function counterTypeId(): ?int
	{
		return $this->counterTypeId;
	}



}