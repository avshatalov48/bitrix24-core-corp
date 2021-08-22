<?php
namespace Bitrix\Tasks\Grid\Project;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Grid\Column;
use Bitrix\Main\Grid\Panel;

Loc::loadMessages(__FILE__);

/**
 * Class Grid
 *
 * @package Bitrix\Tasks\Grid\Project
 */
class Grid extends \Bitrix\Tasks\Grid
{
	public function prepareHeaders(): array
	{
		$headers = [
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_ID'),
				'sort' => 'ID',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_PROJECT'),
				'sort' => 'NAME',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'ACTIVITY_DATE' => [
				'id' => 'ACTIVITY_DATE',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_ACTIVITY_DATE'),
				'sort' => 'ACTIVITY_DATE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'EFFICIENCY' => [
				'id' => 'EFFICIENCY',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_EFFICIENCY'),
				'sort' => false,
				'editable' => false,
				'default' => true,
			],
			'MEMBERS' => [
				'id' => 'MEMBERS',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_MEMBERS'),
				'sort' => false,
				'editable' => false,
				'default' => true,
			],
			'ROLE' => [
				'id' => 'ROLE',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_ROLE'),
				'sort' => false,
				'editable' => false,
				'default' => true,
			],
			'TAGS' => [
				'id' => 'TAGS',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_TAGS'),
				'sort' => false,
				'editable' => false,
				'default' => true,
				'type' => Column\Type::TAGS,
			],
			'OPENED' => [
				'id' => 'OPENED',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_TYPE'),
				'sort' => 'OPENED',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'PROJECT_DATE_START' => [
				'id' => 'PROJECT_DATE_START',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_DATE_START'),
				'sort' => 'PROJECT_DATE_START',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'PROJECT_DATE_FINISH' => [
				'id' => 'PROJECT_DATE_FINISH',
				'name' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_HEADER_DATE_FINISH'),
				'sort' => 'PROJECT_DATE_FINISH',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
		];

		$parameters = $this->getParameters();
		if (
			array_key_exists('GRID_SORT', $parameters)
			&& array_key_exists(($key = key($parameters['GRID_SORT'])), $headers)
		)
		{
			$headers[$key]['color'] = Column\Color::BLUE;
		}

		return $headers;
	}

	public function prepareRows(): array
	{
		$preparedRows = [];

		foreach ($this->getRows() as $key => $data)
		{
			$row = new Row($data, $this->getParameters());
			$preparedRows[$key] = [
				'actions' => $row->prepareActions(),
				'content' => $row->prepareContent(),
				'cellActions' => $row->prepareCellActions(),
				'counters' => $row->prepareCounters(),
			];
		}

		return $preparedRows;
	}

	public function prepareGroupActions(): array
	{
		$actions = [
			[
				'TYPE' => Panel\Types::BUTTON,
				'ID' => 'addToArchive',
				'TEXT' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_GROUP_ACTION_ADD_TO_ARCHIVE'),
				'ICON' => '',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.Projects.ActionsController.doAction('addToArchive')"],
						],
					],
				],
			],
			[
				'TYPE' => Panel\Types::BUTTON,
				'ID' => 'removeFromArchive',
				'TEXT' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_GROUP_ACTION_REMOVE_FROM_ARCHIVE'),
				'ICON' => '',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.Projects.ActionsController.doAction('removeFromArchive')"],
						],
					],
				],
			],
			[
				'TYPE' => Panel\Types::BUTTON,
				'ID' => 'delete',
				'TEXT' => Loc::getMessage('TASKS_GRID_PROJECT_GRID_GROUP_ACTION_DELETE'),
				'ICON' => '',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.Projects.ActionsController.doAction('delete')"],
						],
					],
				],
			],
		];

		return [
			'GROUPS' => [
				['ITEMS' => $actions],
			],
		];
	}
}