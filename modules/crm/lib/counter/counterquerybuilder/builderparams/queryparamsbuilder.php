<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams;

use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Date;

class QueryParamsBuilder
{
	private int $entityTypeId;

	/** @var int[]  */
	private array $userIds;

	private string $selectType;

	private bool $useDistinct = true;

	private bool $needExcludeUsers = false;

	private ?int $counterLimit = null;

	private ?bool $hasAnyIncomingChannel = null;

	private ?Date $periodFrom = null;

	private ?Date $periodTo = null;

	private array $options = [];

	/**
	 * @param int $entityTypeId
	 * @param int[] $userIds
	 * @param string $selectType enum CounterQueryBuilder::SELECT_TYPE_ENTITIES | CounterQueryBuilder::SELECT_TYPE_QUANTITY
	 * @throws ArgumentException
	 */
	public function __construct(
		int $entityTypeId,
		array $userIds,
		string $selectType
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->userIds = array_values(array_unique(array_map('intval', $userIds)));

		if (!in_array($selectType, [CounterQueryBuilder::SELECT_TYPE_ENTITIES, CounterQueryBuilder::SELECT_TYPE_QUANTITY]))
		{
			throw new ArgumentException();
		}
		$this->selectType = $selectType;
	}

	public function setUseDistinct(bool $useDistinct): self
	{
		$this->useDistinct = $useDistinct;
		return $this;
	}

	public function setExcludeUsers(bool $needExcludeUsers): self
	{
		$this->needExcludeUsers = $needExcludeUsers;
		return $this;
	}

	public function setCounterLimit(?int $counterLimit): self
	{
		$this->counterLimit = $counterLimit;
		return $this;
	}

	public function setHasAnyIncomingChannel(?bool $hasAnyIncomingChannel): self
	{
		$this->hasAnyIncomingChannel = $hasAnyIncomingChannel;
		return $this;
	}

	public function setPeriodFrom(?Date $periodFrom): self
	{
		$this->periodFrom = $periodFrom;
		return $this;
	}

	public function setPeriodTo(?Date $periodTo): self
	{
		$this->periodTo = $periodTo;
		return $this;
	}

	public function setOptions(array $options): self
	{
		$this->options = $options;
		return $this;
	}

	public function build(): QueryParams
	{
		return new QueryParams(
			$this->entityTypeId,
			$this->userIds,
			$this->selectType,
			$this->useDistinct,
			$this->needExcludeUsers,
			$this->counterLimit,
			$this->hasAnyIncomingChannel,
			$this->periodFrom,
			$this->periodTo,
			$this->options
		);
	}
}