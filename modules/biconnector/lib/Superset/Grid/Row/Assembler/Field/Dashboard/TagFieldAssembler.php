<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Web\Json;

class TagFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): array
	{
		$ormFilter = $this->getSettings()->getOrmFilter();
		$items = [];
		$dashboardId = (int)$value['ID'];
		$tags = $value['TAGS'] ?? [];
		foreach ($tags as $tag)
		{
			$isActive = isset($ormFilter['TAGS.ID']) && in_array((int)$tag['ID'], $ormFilter['TAGS.ID']);
			$tag['IS_FILTERED'] = $isActive;
			$tag = array_intersect_key($tag, array_flip(['ID', 'TITLE', 'IS_FILTERED']));
			$eventTag = Json::encode($tag);
			$item = [
				'text' => $tag['TITLE'],
				'active' => $isActive,
				'events' => [
					'click' => "BX.delegate((event) => BX.BIConnector.SupersetDashboardGridManager.Instance.handleTagClick('$eventTag'))"
				],
			];

			if ($isActive)
			{
				$item['removeButton'] = [
					'events' => [
						'click' => "BX.delegate((event) => BX.BIConnector.SupersetDashboardGridManager.Instance.handleTagClick('$eventTag'))"
					]
				];
			}

			$items[] = $item;
		}

		$ids = Json::encode(array_column($tags, 'ID'));

		$column = ['items' => $items];

		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_TAG_MODIFY))
		{
			$column['addButton'] = [
				'events' => [
					'click' => "BX.delegate((event) => BX.BIConnector.SupersetDashboardGridManager.Instance.handleTagAddClick({$dashboardId}, '{$ids}', event))"
				]
			];
		}

		return $column;
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];
		foreach ($this->getColumnIds() as $columnId)
		{
			$row['columns'][$columnId] = $this->prepareColumn($row['data']);
		}

		return $row;
	}
}