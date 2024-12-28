<?php

namespace Bitrix\BIConnector\DataSourceConnector;
class FieldDto
{
	public const CONCEPT_TYPE_METRIC = 'METRIC';
	public const CONCEPT_TYPE_DIMENSION = 'DIMENSION';

	public string $type;

	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $description,
		string $type,
		public readonly bool $isMetric,
		public readonly ?bool $isPrimary = false,
		public readonly ?bool $isSystem = true,
		public readonly ?string $aggregationType = null,
		public readonly ?string $groupKey = null,
		public readonly ?string $groupConcat = null,
		public readonly ?string $groupCount = null,
	)
	{
		$this->type = $this->mapType($type);
	}

	/**
	 * Returns internal type external representation.
	 *
	 * @param string $internalType Internal (CSQLWhere) type.
	 * @return string
	 * @see \CSQLWhere
	 */
	protected function mapType(string $internalType): string
	{
		return match ($internalType)
		{
			'file', 'enum', 'int', 'double' => 'NUMBER',
			'date' => 'YEAR_MONTH_DAY',
			'datetime' => 'YEAR_MONTH_DAY_SECOND',
			'bool' => 'BOOLEAN',
			default => 'STRING',
		};
	}


	public function toArray(): array
	{
		return [
			'CONCEPT_TYPE' =>
				$this->isMetric
					? self::CONCEPT_TYPE_METRIC
					: self::CONCEPT_TYPE_DIMENSION
			,
			'ID' => $this->id,
			'NAME' => $this->name,
			'DESCRIPTION' => $this->description,
			'TYPE' => $this->type,
			'IS_PRIMARY' => $this->isPrimary ? 'Y' : 'N',
			'AGGREGATION_TYPE' => $this->aggregationType,
			'GROUP_KEY' => $this->groupKey,
			'GROUP_CONCAT' => $this->groupConcat,
			'GROUP_COUNT' => $this->groupCount,
		];
	}
}
