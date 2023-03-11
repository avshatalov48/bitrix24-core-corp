<?php

namespace Bitrix\Tasks\Grid\Tag\Row;

class Content
{
	protected array $rowData = [];
	protected array $parameters = [];
	protected ?string $fieldKey;

	public function __construct(array $rowData = [], array $parameters = [], string $fieldKey = null)
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
		$this->fieldKey = $fieldKey;
	}

	public function prepare()
	{
		$resultRow = [];

		$prepareMap = [
			'ID' => Content\Id::class,
			'NAME' => Content\Name::class,
			'COUNT' => Content\Count::class,
		];

		foreach ($prepareMap as $key => $value)
		{
			/** @var Content $class */
			$class = $value;
			$resultRow[$key] = (new $class($this->rowData, $this->parameters, $key))->prepare();
		}
		return $resultRow;
	}

	public function getRowData(): array
	{
		return $this->rowData;
	}

	public function setRowData(array $rowData): void
	{
		$this->rowData = $rowData;
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