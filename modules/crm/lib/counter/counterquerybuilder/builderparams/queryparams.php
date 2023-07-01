<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams;


use Bitrix\Main\Type\Date;

final class QueryParams
{
	private int $entityTypeId;

	/** @var int[]  */
	private array $userIds;

	private string $selectType;

	private bool $useDistinct;

	private bool $needExcludeUsers;

	private ?int $counterLimit;

	private ?bool $hasAnyIncomingChannel;

	private ?Date $periodFrom;

	private ?Date $periodTo;

	private array $options;

	public function __construct(
		int $entityTypeId,
		array $userIds,
		string $selectType,
		bool $useDistinct,
		bool $needExcludeUsers,
		?int $counterLimit,
		?bool $hasAnyIncomingChannel,
		?Date $periodFrom,
		?Date $periodTo,
		array $options
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->selectType = $selectType;
		$this->userIds = $userIds;
		$this->useDistinct = $useDistinct;
		$this->needExcludeUsers = $needExcludeUsers;
		$this->counterLimit = $counterLimit;
		$this->hasAnyIncomingChannel = $hasAnyIncomingChannel;
		$this->periodFrom = $periodFrom;
		$this->periodTo = $periodTo;
		$this->options = $options;
	}

	public function entityTypeId(): int
	{
		return $this->entityTypeId;
	}

	/**
	 * @return int[]
	 */
	public function userIds(): array
	{
		return $this->userIds;
	}

	public function getSelectType(): string
	{
		return $this->selectType;
	}

	public function useDistinct(): bool
	{
		return $this->useDistinct;
	}

	public function excludeUsers(): bool
	{
		return $this->needExcludeUsers;
	}

	public function counterLimit(): ?int
	{
		return $this->counterLimit;
	}

	public function hasAnyIncomingChannel(): ?bool
	{
		return $this->hasAnyIncomingChannel;
	}

	public function periodFrom(): ?Date
	{
		return $this->periodFrom;
	}

	public function periodTo(): ?Date
	{
		return $this->periodTo;
	}

	public function options(): array
	{
		return $this->options;
	}

}