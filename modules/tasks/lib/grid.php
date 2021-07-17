<?php
namespace Bitrix\Tasks;

/**
 * Class Grid
 *
 * @package Bitrix\Tasks
 */
abstract class Grid
{
	protected $rows = [];
	protected $parameters = [];

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

	public function setRows(array $rows): void
	{
		$this->rows = $rows;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}
}