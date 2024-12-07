<?php
namespace Bitrix\Tasks\Grid;

use Bitrix\Tasks\Grid\Scope\ScopeStrategyFactory;

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

	public function setScope(?string $scope): static
	{
		$this->parameters['SCOPE'] = (string)$scope;

		return $this;
	}

	protected function applyStrategies(): static
	{
		$strategies = ScopeStrategyFactory::getStrategies($this->parameters);
		foreach ($strategies as $strategy)
		{
			$strategy->apply($this->headers, $this->parameters);
		}

		return $this;
	}
}
