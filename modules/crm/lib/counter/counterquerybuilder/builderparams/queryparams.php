<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams;


use Bitrix\Main\Type\Date;

final class QueryParams
{
	private int $entityTypeId;

	private string $selectType;

	private bool $useDistinct;

	private ?int $counterLimit;

	private ?bool $hasAnyIncomingChannel;

	private ?Date $periodFrom;

	private ?Date $periodTo;

	private array $options;

	private UserParams $userParams;

	private bool $useActivityResponsible;

	/**
	 * @todo description
	 * @var Date|null
	 */
	private ?Date $restrictedFrom;

	public function __construct(
		int $entityTypeId,
		string $selectType,
		bool $useDistinct,
		?int $counterLimit,
		?bool $hasAnyIncomingChannel,
		?Date $periodFrom,
		?Date $periodTo,
		array $options,
		?Date $restrictedFrom,
		UserParams $userParams,
		bool $useActivityResponsible,
		bool $completed
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->selectType = $selectType;
		$this->useDistinct = $useDistinct;
		$this->counterLimit = $counterLimit;
		$this->hasAnyIncomingChannel = $hasAnyIncomingChannel;
		$this->periodFrom = $periodFrom;
		$this->periodTo = $periodTo;
		$this->options = $options;
		$this->restrictedFrom = $restrictedFrom;
		$this->userParams = $userParams;
		$this->useActivityResponsible = $useActivityResponsible;
		$this->completed = $completed;
	}

	public function entityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getSelectType(): string
	{
		return $this->selectType;
	}

	public function useDistinct(): bool
	{
		return $this->useDistinct;
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

	public function restrictedFrom(): ?Date
	{
		return $this->restrictedFrom;
	}

	public function userParams(): UserParams
	{
		return $this->userParams;
	}

	/**
	 * Return first user in filter ID. It will be used to detect timezone for the date filters
	 *
	 * @return int|null
	 */
	public function firstUserId(): ?int
	{
		return $this->userParams->firstUserId();
	}

	public function useActivityResponsible(): bool
	{
		return $this->useActivityResponsible;
	}

	public function isCompleted(): bool
	{
		return $this->completed;
	}
}