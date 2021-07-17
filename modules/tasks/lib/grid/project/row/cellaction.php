<?php
namespace Bitrix\Tasks\Grid\Project\Row;

use Bitrix\Main\Grid;

/**
 * Class CellAction
 *
 * @package Bitrix\Tasks\Grid\Project\Row
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
		$isPinned = ($this->rowData['IS_PINNED'] === 'Y');

		return [
			'NAME' => [
				[
					'class' => [
						Grid\CellActions::PIN,
						($isPinned ? Grid\CellActionState::ACTIVE : Grid\CellActionState::SHOW_BY_HOVER),
					],
					'events' => [
						'click' => 'BX.Tasks.Projects.ActionsController.changePin',
					],
				],
			],
		];
	}
}