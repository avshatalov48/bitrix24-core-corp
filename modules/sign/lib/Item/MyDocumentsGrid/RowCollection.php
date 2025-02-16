<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<Row>
 */
class RowCollection extends Collection
{
	public function getItemClassName(): string
	{
		return Row::class;
	}
}