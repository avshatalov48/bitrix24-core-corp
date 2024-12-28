<?php

namespace Bitrix\BIConnector\DataSourceConnector;
final class ConnectorDto
{
	public function __construct(
		public readonly array $schema,
		public readonly string $query,
		public readonly array $queryRowCallbacks,
		public readonly array $filter,
		public readonly bool $filtersApplied,
		public readonly array $shadowFields,
	)
	{
	}

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return array_column($this->schema, 'ID');
	}


	/**
	 * @param string $code
	 * @return mixed
	 */
	public function getFilterValue(string $code): mixed
	{
		return $this->filter[$code] ?? null;
	}
}
