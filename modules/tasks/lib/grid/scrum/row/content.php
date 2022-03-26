<?php
namespace Bitrix\Tasks\Grid\Scrum\Row;

/**
 * Class Content
 *
 * @package Bitrix\Tasks\Grid\Scrum\Row
 */
class Content
{
	protected $rowData = [];
	protected $parameters = [];

	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	public function prepare()
	{
		$resultRow = [];

		$prepareMap = [
			'ID' => Content\ProjectId::class,
			'NAME' => Content\Name::class,
			'EFFICIENCY' => Content\Efficiency::class,
			'MEMBERS' => Content\Members::class,
			'ROLE' => Content\Role::class,
			'TAGS' => Content\Tags::class,
			'OPENED' => Content\Type::class,

			'ACTIVITY_DATE' => Content\Date\ActivityDate::class,
			'PROJECT_DATE_START' => Content\Date\StartDate::class,
			'PROJECT_DATE_FINISH' => Content\Date\EndDate::class,
		];
		foreach ($prepareMap as $key => $class)
		{
			/** @var Content $class */
			$resultRow[$key] = (new $class($this->rowData, $this->parameters))->prepare();
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