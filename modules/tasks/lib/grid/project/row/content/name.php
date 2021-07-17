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

		$image = UI::getAvatarFile($row['IMAGE_ID'], ['WIDTH' => 30, 'HEIGHT' => 30]);
		$imageSrc = $image['RESIZED']['SRC'];

		$name = htmlspecialcharsbx($row['NAME']);
		$path = htmlspecialcharsbx($row['PATH']);

		$photo = ($imageSrc ? "<i style='background-image: url(\"{$imageSrc}\")'></i>" : "<i></i>");

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
