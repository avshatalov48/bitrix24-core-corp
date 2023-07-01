<?php
namespace Bitrix\Tasks\Grid\Task\Row;

use \Bitrix\Main\Grid;
use Bitrix\Tasks\Internals\Counter\Template\CounterStyle;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;

/**
 * Class Counter
 *
 * @package Bitrix\Tasks\Grid\Task\Row
 */
class Counter
{
	protected $rowData = [];
	protected $parameters = [];

	/**
	 * Action constructor.
	 *
	 * @param array $rowData
	 * @param array $parameters
	 */
	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	public function prepare(): array
	{
		$userId = (int)$this->parameters['USER_ID'];
		$taskId = (int)$this->rowData['ID'];
		$groupId = (int)$this->rowData['GROUP_ID'];

		$colorMap = [
			CounterStyle::STYLE_GRAY => Grid\Counter\Color::GRAY,
			CounterStyle::STYLE_GREEN => Grid\Counter\Color::SUCCESS,
			CounterStyle::STYLE_RED => Grid\Counter\Color::DANGER,
		];

		$rowCounter = [
			'VALUE' => 0,
			'COLOR' => Grid\Counter\Color::GRAY
		];

		if (
			array_key_exists('CAN_SEE_COUNTERS', $this->parameters)
			&& $this->parameters['CAN_SEE_COUNTERS']
		)
		{
			$rowCounter = (new TaskCounter($userId))->getRowCounter($taskId);
		}

		$taskUrlTemplate = (
			$groupId > 0 ? RouteDictionary::PATH_TO_GROUP_TASK : RouteDictionary::PATH_TO_USER_TASK
		);
		$taskUrl = \CComponentEngine::MakePathFromTemplate($taskUrlTemplate, [
			'user_id' => $userId,
			'task_id' => $taskId,
			'group_id' => $groupId,
			'action' => 'view',
		]);

		return [
			'ACTIVITY_DATE' => [
				'type' => Grid\Counter\Type::LEFT_ALIGNED,
				'color' => ($colorMap[$rowCounter['COLOR']] ?? null),
				'value' => $rowCounter['VALUE'],
				'events' => [
					'click' => "BX.SidePanel.Instance.open.bind(BX.SidePanel.Instance, '{$taskUrl}')",
				],
				'class' => 'tasks-list-cursor',
			],
		];
	}
}