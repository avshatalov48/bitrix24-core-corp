<?php
namespace Bitrix\Tasks\Grid\Project\Row;

use \Bitrix\Main\Grid;
use Bitrix\Tasks\Internals\Counter\Template\CounterStyle;
use Bitrix\Tasks\Internals\Counter\Template\ProjectCounter;

/**
 * Class Counter
 *
 * @package Bitrix\Tasks\Grid\Project\Row
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
		$groupId = (int)$this->rowData['ID'];

		$counter = [
			'COLOR' => CounterStyle::STYLE_GRAY,
			'VALUE' => 0,
		];

		if (
			array_key_exists($userId, $this->rowData['MEMBERS']['HEADS'])
			|| array_key_exists($userId, $this->rowData['MEMBERS']['MEMBERS'])
		)
		{
			$counter = (new ProjectCounter($userId))->getRowCounter($groupId);
		}

		$colorMap = [
			CounterStyle::STYLE_GRAY => Grid\Counter\Color::GRAY,
			CounterStyle::STYLE_GREEN => Grid\Counter\Color::SUCCESS,
			CounterStyle::STYLE_RED => Grid\Counter\Color::DANGER,
		];

		return [
			'ACTIVITY_DATE' => [
				'type' => Grid\Counter\Type::LEFT_ALIGNED,
				'color' => $colorMap[$counter['COLOR']],
				'value' => $counter['VALUE'],
				'events' => [
					'click' => 'BX.Tasks.Projects.ActionsController.onCounterClick',
				],
				'class' => 'tasks-projects-counter',
			],
		];
	}
}