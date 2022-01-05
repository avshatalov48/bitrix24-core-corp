<?php

namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Tasks\Grid\Project\Row\Content;
use Bitrix\Tasks\UI;

/**
 * Class Name
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Name extends Content
{
	public function prepare(): string
	{
		$row = $this->getRowData();

		$name = htmlspecialcharsbx($row['NAME']);
		$path = htmlspecialcharsbx($row['PATH']);

		$photo = ($row['IMAGE'] ? "<i style='background-image: url(\"{$row['IMAGE']}\")'></i>" : "<i></i>");

		return "
			<div class='tasks-projects-box'>
				<div class='ui-icon ui-icon-common-user-group tasks-projects-icon'>$photo</div>
				<a
					class='tasks-projects-text'
					href='{$path}'
				>{$name}</a>
			</div>
		";
	}
}
