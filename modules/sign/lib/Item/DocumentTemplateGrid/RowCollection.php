<?php

namespace Bitrix\Sign\Item\DocumentTemplateGrid;

use Bitrix\Sign\Item\Collection;
use Bitrix\Sign\Item\Document\Template;

/**
 * @extends Collection<Row>
 */
class RowCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return Row::class;
	}

	/**
	 * @return list<?int>
	 */
	public function getIds(): array
	{
		return array_map(
			static fn(Row $row): int => $row->id,
			$this->toArray(),
		);
	}
}