<?php


namespace Bitrix\Market\ListTemplates;


abstract class BaseTemplate
{
	protected array $result = [];

	protected array $filter = [];

	protected array $order = [];

	protected int $page = 1;

	protected array $requestParams = [];

	public function __construct($requestParams = [])
	{
		$this->requestParams = $requestParams;
	}

	abstract public function setResult(bool $isAjax = false);

	public function getInfo(): array
	{
		return $this->result;
	}

	public function setFilter(array $filter): void
	{
		$this->filter = $filter;
	}

	public function setOrder(array $order): void
	{
		$this->order = $order;
	}

	public function setPage(int $page): void
	{
		$this->page = $page;
	}
}