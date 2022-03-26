<?php

namespace Bitrix\Crm\Search\Result;

use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Service\Container;

abstract class Provider
{
	public const DEFAULT_LIMIT = 20;

	protected $userId;
	protected $limit;
	protected $additionalFilter = [];
	protected $useDenominationSearch = false;

	public function __construct()
	{
		$this->limit = self::DEFAULT_LIMIT;
		$this->userId = Container::getInstance()->getContext()->getUserId();
	}

	public function setLimit(int $limit)
	{
		$this->limit = $limit;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function setUserId(int $userId)
	{
		$this->userId = $userId;
	}

	public function setAdditionalFilter(array $filter)
	{
		$this->additionalFilter = $filter;
	}

	public function setUseDenominationSearch(bool $useDenominationSearch)
	{
		$this->useDenominationSearch = $useDenominationSearch;
	}

	abstract public function getSearchResult(string $searchQuery): Result;
}
