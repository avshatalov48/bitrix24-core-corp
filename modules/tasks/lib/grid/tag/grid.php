<?php

namespace Bitrix\Tasks\Grid\Tag;

use Bitrix\Main\Localization\Loc;

class Grid extends \Bitrix\Tasks\Grid\Grid
{
	public function prepareHeaders(): array
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_USER_TAGS_GRID_COLUMN_NAME'),
				'sort' => 'NAME',
				'first_order' => 'asc',
				'default' => true,
				'editable' => true,
				'width' => 400,
			],
			'COUNT' => [
				'id' => 'COUNT',
				'name' => Loc::getMessage('TASKS_USER_TAGS_GRID_COLUMN_COUNT'),
				'sort' => 'COUNT',
				'default' => true,
				'editable' => false,
				'width' => 300,
			],
		];
	}

	public function prepareRows(): array
	{
		$preparedRows = [];

		foreach ($this->getRows() as $key => $data)
		{
			$row = new Row($data, $this->getParameters());
			$preparedRows[$key] = [
				'actions' => $row->prepareActions(),
				'data' => $row->prepareContent(),
			];
		}

		return $preparedRows;
	}

	public function prepareGroupActions(): array
	{
		return (new GroupAction())->prepareGroupAction();
	}
}