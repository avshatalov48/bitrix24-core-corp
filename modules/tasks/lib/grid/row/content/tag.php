<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class Tag
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Tag extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$tags = [];

		if (!array_key_exists('TAG', $row) || !is_array($row['TAG']))
		{
			return '';
		}

		foreach ($row['TAG'] as $tag)
		{
			$safeTag = htmlspecialcharsbx($tag);
			$encodedData = Json::encode(['TAG' => $safeTag]);

			$selector = 'tasks-grid-tag';
			if (
				isset($parameters['FILTER_FIELDS']['TAG'])
				&& $parameters['FILTER_FIELDS']['TAG'] === $safeTag
			)
			{
				$selector .= ' tasks-grid-filter-active';
			}

			$tags[]
				= "<a class='". $selector ."' href='javascript:void(0)' onclick='BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData})'>"
				. "#{$safeTag}"
				. "</a>";
		}

		return implode(', ', $tags);
	}
}