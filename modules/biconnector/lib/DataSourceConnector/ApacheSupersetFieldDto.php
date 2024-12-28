<?php

namespace Bitrix\BIConnector\DataSourceConnector;
final class ApacheSupersetFieldDto extends FieldDto
{
	/**
	 * Returns internal type external representation.
	 *
	 * @param string $internalType Internal (CSQLWhere) type.
	 * @return string
	 * @see \CSQLWhere
	 */
	protected function mapType(string $internalType): string
	{
		if (str_starts_with($this->id, 'UF_') && !$this->isSystem)
		{
			return 'STRING';
		}

		return match ($internalType)
		{
			'file', 'enum', 'int' => 'INT',
			'double' => 'DOUBLE',
			'date' => 'DATE',
			'datetime' => 'DATETIME',
			'bool' => 'BOOLEAN',
			'array_string' => 'ARRAY_STRING',
			'map_string' => 'MAP_STRING',
			default => 'STRING',
		};
	}
}
