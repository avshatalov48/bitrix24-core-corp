<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Project\Row\Content;

/**
 * Class Tags
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Tags extends Content
{
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$tags = [];
		if (array_key_exists('TAGS', $row) && is_array($row['TAGS']))
		{
			$tags = $row['TAGS'];
		}

		$tagsLayout = '';
		foreach ($tags as $tag)
		{
			$safeTag = htmlspecialcharsbx($tag);
			$encodedData = Json::encode(['TAGS' => $safeTag]);

			$selector =
				isset($parameters['FILTER_DATA']['TAGS']) && $parameters['FILTER_DATA']['TAGS'] === $safeTag
					? 'tasks-projects-grid-tag tasks-projects-grid-filter-active'
					: 'tasks-projects-grid-tag'
			;

			$tagsLayout .=
				"<div class='ui-label ui-label-fill ui-label-tag-light {$selector}' onclick='BX.PreventDefault(); BX.Tasks.ProjectsInstance.getFilter().toggleByField({$encodedData});'>"
				. "<span class='ui-label-inner'>{$safeTag}</span>"
				. "<span class='tasks-projects-grid-filter-remove'></span>"
				. "</div>"
			;
		}

		return $tagsLayout;
	}
}