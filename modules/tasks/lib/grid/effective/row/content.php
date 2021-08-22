<?php
namespace Bitrix\Tasks\Grid\Effective\Row;

use Bitrix\Main;

/**
 * Class Content
 * @package Bitrix\Tasks\Grid\Effective\Row
 */
class Content
{
	protected $rowData = [];
	protected $parameters = [];
	protected $fieldKey;

	public function __construct(array $rowData = [], array $parameters = [], string $fieldKey = null)
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
		$this->fieldKey = $fieldKey;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	public function prepare()
	{
		$resultRow = [];

		if (array_key_exists('REAL_STATUS', $this->rowData))
		{
			$this->rowData['REAL_STATUS'] = (int)$this->rowData['REAL_STATUS'];
		}
		elseif (array_key_exists('STATUS', $this->rowData))
		{
			$this->rowData['REAL_STATUS'] = (int)$this->rowData['STATUS'];
		}

		$prepareMap = [
			'DEADLINE' => Content\Date\FormattedDate::class,
			'DATE' => Content\Date\FormattedDate::class,
			'DATE_REPAIR' => Content\Date\FormattedDate::class,
		];

		foreach ($this->rowData as $key => $value)
		{
			if (array_key_exists($key, $prepareMap))
			{
				/** @var Content $class */
				$class = $prepareMap[$key];
				$resultRow[$key] = (new $class($this->rowData, $this->parameters, $key))->prepare();
			}
			else
			{
				$resultRow[$key] = $value;
			}
		}

		foreach ($prepareMap as $key => $value)
		{
			if (array_key_exists($key, $resultRow))
			{
				continue;
			}
			$resultRow[$key] = (new $value($this->rowData, $this->parameters, $key))->prepare();
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