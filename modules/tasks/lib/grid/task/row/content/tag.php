<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Tag
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Tag extends Content
{
	/**
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$tags = '';

		if (!array_key_exists('TAG', $row) || !is_array($row['TAG']))
		{
			return $tags;
		}

		foreach ($row['TAG'] as $tag)
		{
			$safeTag = htmlspecialcharsbx($tag);
			$encodedData = Json::encode(['TAG' => $safeTag]);

			$selected = 0;
			$selector = 'tasks-grid-tag';
			if (
				isset($parameters['FILTER_FIELDS']['TAG'])
				&& $parameters['FILTER_FIELDS']['TAG'] === $safeTag
			)
			{
				$selected = 1;
				$selector .= ' tasks-grid-filter-active';
			}

			$tags .=
				"<div class='ui-label ui-label-fill ui-label-tag-light {$selector}' onclick='BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData}, {$selected});'>"
				. "<span class='ui-label-inner'>{$safeTag}</span>"
				. "<span class='tasks-grid-filter-remove'></span>"
				. "</div>"
			;
		}

		return $tags;
	}
}