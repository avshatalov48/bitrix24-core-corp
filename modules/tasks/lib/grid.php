<?php
namespace Bitrix\Tasks;

use Bitrix\Tasks\Grid\Scope\ScopeFactory;
use Bitrix\Tasks\Grid\ScopeInterface;

/**
 * Class Grid
 *
 * @package Bitrix\Tasks
 */
abstract class Grid
{
	protected array $headers = [];
	protected ?ScopeInterface $scope = null;

	public function __construct(protected array $rows = [], protected array $parameters = [])
	{
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
		$this->scope = ScopeFactory::getScope((string)$scope, $this);
		return $this;
	}

	public function isInScope(): bool
	{
		return !is_null($this->scope);
	}
}
