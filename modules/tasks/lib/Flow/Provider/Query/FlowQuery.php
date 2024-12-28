<?php

namespace Bitrix\Tasks\Flow\Provider\Query;

use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Provider\Query\AbstractTaskQuery;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class FlowQuery extends AbstractTaskQuery implements TaskQueryInterface
{
	protected bool $countTotal = false;
	protected bool $accessCheck = true;
	protected bool $onlyPrimaries = false;

	protected int $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;

		$this->init();
	}

	public function setPageNavigation(PageNavigation $pageNavigation): static
	{
		return $this
			->setLimit($pageNavigation->getLimit())
			->setOffset($pageNavigation->getOffset());
	}

	public function setOnlyPrimaries(bool $onlyPrimaries = true): static
	{
		$this->onlyPrimaries = $onlyPrimaries;
		return $this;
	}

	public function setCountTotal(bool $countTotal): static
	{
		$this->countTotal = $countTotal;

		return $this;
	}

	public function setAccessCheck(bool $check): static
	{
		$this->accessCheck = $check;
		return $this;
	}
	
	public function getId(): string
	{
		return 0;
	}

	public function getCountTotal(): int
	{
		return $this->countTotal;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function needAccessCheck(): bool
	{
		return $this->accessCheck;
	}

	public function getOnlyPrimaries(): bool
	{
		return $this->onlyPrimaries;
	}

	public function isOnlyPrimaries(): bool
	{
		return $this->select === ['ID'];
	}

	protected function init(): void
	{

	}
}
