<?php

namespace Bitrix\Tasks\Grid\Tag\Row\Content;

use Bitrix\Tasks\Grid\Tag\Row\Content;

class Count extends Content
{
	public function prepare(): string
	{
		$count = (int)$this->getRowData()['COUNT'];
		$tagId = (int)$this->getRowData()['ID'];
		$onclick = "BX.Tasks.TagActionsObject.show({$tagId}, {$count});";
		$style = '';
		if ($count === 0)
		{
			$onclick = '';
			$style = 'text-decoration: none; cursor: default;';
		}

		return "
		<a class=\"tasks-by-tag-class\" onclick=\"$onclick\" style=\"$style\">
			$count
		</a>
		";
	}
}