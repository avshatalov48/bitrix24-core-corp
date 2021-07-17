<?php
namespace Bitrix\Tasks\Grid\Task\Row;

use Bitrix\Main\Grid;

/**
 * Class CellAction
 *
 * @package Bitrix\Tasks\Grid\Task\Row
 */
class CellAction
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

	/**
	 * @return \array[][]
	 */
	public function prepare(): array
	{
		$canPin = $this->parameters['CAN_USE_PIN'];
		$isMuted = ($this->rowData['IS_MUTED'] === 'Y');

		if ($this->parameters['GROUP_ID'])
		{
			$isPinned = array_key_exists('IS_PINNED_IN_GROUP', $this->rowData) && $this->rowData['IS_PINNED_IN_GROUP'] === 'Y';
		}
		else
		{
			$isPinned = ($this->rowData['IS_PINNED'] === 'Y');
		}

		$cellActions = [
			'TITLE' => [],
		];

		if ($canPin)
		{
			$cellActions['TITLE'][] = [
				'class' => [
					Grid\CellActions::PIN,
					($isPinned ? Grid\CellActionState::ACTIVE : Grid\CellActionState::SHOW_BY_HOVER),
				],
				'events' => [
					'click' => 'BX.Tasks.GridActions.changePin',
				],
			];
		}

		$cellActions['TITLE'][] = [
			'class' => [
				Grid\CellActions::MUTE,
				($isMuted ? Grid\CellActionState::ACTIVE : Grid\CellActionState::SHOW_BY_HOVER),
			],
			'events' => [
				'click' => 'BX.Tasks.GridActions.changeMute',
			],
		];

		return $cellActions;
	}
}