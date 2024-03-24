<?php
namespace Bitrix\Tasks;

use Bitrix\Tasks\Grid\Scope\ScopeStrategyFactory;
use Bitrix\Tasks\Grid\ScopeStrategyInterface;

/**
 * Class Grid
 *
 * @package Bitrix\Tasks
 */
abstract class Grid
{
	protected array $rows = [];
	protected array $parameters = [];
	protected array $headers = [];
	protected ?ScopeStrategyInterface $scopeStrategy = null;

	public function __construct(array $rows = [], array $parameters = [])
	{
		$this->rows = $rows;
		$this->parameters = $parameters;
	}

	abstract public function prepareHeaders(): array;
	abstract public function prepareRows(): array;
	abstract public function prepareGroupActions(): array;

	public function getRows(): array
	{
		return $this->rows;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setRows(array $rows): void
	{
		$this->rows = $rows;
	}

	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	public function setScopeStrategy(?string $scopeStrategy): static
	{
		$this->scopeStrategy = ScopeStrategyFactory::getStrategy((string)$scopeStrategy);
		return $this;
	}

	protected function applyScopeStrategy(): static
	{
		if ($this->isInScope())
		{
			$this->headers = $this->scopeStrategy->apply($this->headers);
		}

		return $this;
	}

	private function isInScope(): bool
	{
		return !is_null($this->scopeStrategy);
	}
}
