<?php
namespace Bitrix\Tasks\Grid\Scrum\Row\Content;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Scrum\Row\Content;

/**
 * Class Tags
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Tags extends Content
{
	public function prepare(): array
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$tags = [
			'items' => [],
		];

		$userId = (int)$parameters['USER_ID'];
		$user = [];
		if (array_key_exists($userId, $row['MEMBERS']['HEADS']))
		{
			$user = $row['MEMBERS']['HEADS'][$userId];
		}
		elseif (array_key_exists($userId, $row['MEMBERS']['MEMBERS']))
		{
			$user = $row['MEMBERS']['MEMBERS'][$userId];
		}

		if ($user['IS_OWNER'] === 'Y')
		{
			$tags['addButton'] = [
				'events' => [
					'click' => "BX.Tasks.Projects.ActionsController.onTagAddClick.bind(BX.Tasks.Projects.ActionsController, {$row['ID']})",
				],
			];
		}

		if (!array_key_exists('TAGS', $row) || !is_array($row['TAGS']))
		{
			return $tags;
		}

		foreach ($row['TAGS'] as $tag)
		{
			$encodedData = Json::encode(['TAGS' => $tag]);
			$selected = (isset($parameters['FILTER_DATA']['TAGS']) && $parameters['FILTER_DATA']['TAGS'] === $tag);

			$tags['items'][] = [
				'text' => $tag,
				'active' => $selected,
				'events' => [
					'click' => "BX.Tasks.Projects.ActionsController.onTagClick.bind(BX.Tasks.Projects.ActionsController, {$encodedData})",
				],
			];
		}

		return $tags;
	}
}