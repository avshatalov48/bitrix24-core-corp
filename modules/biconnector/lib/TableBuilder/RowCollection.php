<?php

namespace Bitrix\BIConnector\TableBuilder;

use Bitrix\BIConnector\Collection;

class RowCollection extends Collection
{
	public function addRows(array $rows): void
	{
		foreach ($rows as $row)
		{
			$this->add($row);
		}
	}
}
