<?php
namespace Bitrix\Tasks\Grid\Tag\Row\Content;

use Bitrix\Tasks\Grid\Tag\Row\Content;

class Name extends Content
{
	public function prepare(): string
	{
		return htmlspecialcharsbx($this->getRowData()['NAME']);
	}
}