<?php
namespace Bitrix\Tasks\Grid;

/**
 * Class Row
 *
 * @package Bitrix\Tasks\Grid
 */
abstract class Row
{
	protected $data = [];
	protected $parameters = [];

	public function __construct(array $data = [], array $parameters = [])
	{
		$this->data = $data;
		$this->parameters = $parameters;
	}

	abstract public function prepareActions(): array;
	abstract public function prepareContent(): array;
	abstract public function prepareCellActions(): array;
	abstract public function prepareCounters(): array;

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): void
	{
		$this->data = $data;
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