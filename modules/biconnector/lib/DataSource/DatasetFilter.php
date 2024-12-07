<?php

namespace Bitrix\BIConnector\DataSource;

class DatasetFilter
{
	public function __construct(
		private readonly array $datasetFilter,
		private readonly array $filterFields
	)
	{}

	/**
	 * @return array
	 */
	public function datasetFilter(): array
	{
		return $this->datasetFilter;
	}

	/**
	 * @return DatasetField[]
	 */
	public function filterFields(): array
	{
		$fields = [];
		foreach ($this->filterFields as $field)
		{
			if ($field instanceof DatasetField)
			{
				$fields[] = $field;
			}
		}

		return $fields;
	}
}
