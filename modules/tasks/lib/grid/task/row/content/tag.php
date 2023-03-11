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
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public function prepare(): array
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$tags = [
			'items' => [],
		];

		if ($row['ACTION']['EDIT'])
		{
			$params = "{$row['ID']}, {$row['GROUP_ID']}";

			$tags['addButton'] = [
				'events' => [
					'click' => "BX.Tasks.GridActions.onTagUpdateClick.bind(BX.Tasks.GridActions, {$params})",
				],
			];
		}

		if (!array_key_exists('TAG', $row) || !is_array($row['TAG']))
		{
			return $tags;
		}

		$filterTags = ($parameters['FILTER_FIELDS']['TAG'] ?? []);
		$filterTags = (is_array($filterTags) ? $filterTags: [$filterTags]);

		foreach ($row['TAG'] as $tag)
		{
			$encodedData = Json::encode([
				'TAG' => [$tag],
				'TAG_label' => [$tag],
			]);
			$selected = (int)in_array($tag, $filterTags, true);

			$tags['items'][] = [
				'text' => $tag,
				'active' => (bool)$selected,
				'events' => [
					'click' => "BX.Tasks.GridActions.toggleFilter.bind(BX.Tasks.GridActions, {$encodedData}, {$selected}, true)",
				],
			];
		}

		return $tags;
	}
}