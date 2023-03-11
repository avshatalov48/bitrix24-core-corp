<?php

namespace Bitrix\Tasks\Grid\Scrum\Row\Content;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Grid\Scrum\Row\Content;
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

		$photo = ($imageSrc ? '<i style="background-image: url(\'' . Uri::urnEncode($imageSrc) . '\')"></i>' : "<i></i>");

		$iconClassList = [
			'tasks-projects-icon',
		];

		if (
			!$imageSrc
			&& !empty($row['AVATAR_TYPE'])
			&& Loader::includeModule('socialnetwork')
		)
		{
			$iconClassList[] = 'sonet-common-workgroup-avatar';
			$iconClassList[] = '--' . htmlspecialcharsbx(\Bitrix\Socialnetwork\Helper\Workgroup::getAvatarTypeWebCssClass($row['AVATAR_TYPE']));
		}
		else
		{
			$iconClassList[] = 'ui-icon';
			$iconClassList[] = 'ui-icon-common-user-group';
		}

		return "
			<div class='tasks-projects-box'>
				<div class='" . implode(' ', $iconClassList) . "'>$photo</div>
				<a 
					class='tasks-projects-text' 
					href='{$path}'
				>{$name}</a>
			</div>
		";
	}
}
