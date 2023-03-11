<?php

namespace Bitrix\Tasks\Grid\Tag\Row\Content;

use Bitrix\Tasks\Grid\Tag\Row\Content;

class Id extends Content
{
	public function prepare(): int
	{
		return (int)$this->getRowData()['ID'];
	}
}