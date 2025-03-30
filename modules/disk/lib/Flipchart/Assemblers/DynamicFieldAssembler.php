<?php

namespace Bitrix\Disk\Flipchart\Assemblers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;

final class DynamicFieldAssembler extends FieldAssembler
{

	private ?\Closure $assemblerCallable = null;

	public function __construct(array $columnIds, \Closure $assemblerCallable, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
		$this->assemblerCallable = $assemblerCallable;
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$row['columns'][$columnId] = $this->prepareColumn($row['data'][$columnId] ?? null, $row, $columnId);
		}

		return $row;
	}

	protected function prepareColumn($value, ?array $row = null, ?string $columnId = null)
	{
		$foo = $this->assemblerCallable;
		return $foo($value, $row, $columnId);
	}

}