<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Grid\Task;

use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Types;

Loc::loadMessages(__FILE__);

class GroupAction
{
	public const ACTION_NONE = 'none';
	public const ACTION_PING = 'ping';
	public const ACTION_COMPLETE = 'complete';
	public const ACTION_SET_DEADLINE = 'setdeadline';
	public const ACTION_ADJUST_DEADLINE = 'adjustdeadline';
	public const ACTION_SUBSTRACT_DEADLINE = 'substractdeadline';
	public const ACTION_SET_TASK_CONTROL = 'settaskcontrol';
	public const ACTION_SET_RESPONSIBLE = 'setresponsible';
	public const ACTION_SET_ORIGINATOR = 'setoriginator';
	public const ACTION_ADD_AUDITOR = 'addauditor';
	public const ACTION_ADD_ACCOMPLICE = 'addaccomplice';
	public const ACTION_MUTE = 'mute';
	public const ACTION_UNMUTE = 'unmute';
	public const ACTION_ADD_FAVORITE = 'addtofavorite';
	public const ACTION_REMOVE_FAVORITE = 'removefromfavorite';
	public const ACTION_SET_GROUP = 'setgroup';
	public const ACTION_DELETE = 'delete';


	public function __construct()
	{

	}

	public function prepareGroupActions($gridId, array $disabledActions = []): array
	{
		$snippet = new Snippet();

		return [
			'GROUPS' => [
				[
					'ITEMS' => [
						[
							'ID' => "action_button_{$gridId}",
							'NAME' => "action_button_{$gridId}",
							'TYPE' => Types::DROPDOWN,
							'ITEMS' => $this->getActionList($disabledActions),
						],
						$snippet->getApplyButton([
							'ONCHANGE' => [
								[
									'ACTION' => Actions::CALLBACK,
									'DATA' => [
										['JS' => "BX.Tasks.GridActions.confirmGroupAction('{$gridId}')"],
									],
								],
							],
						]),
						$snippet->getForAllCheckbox(),
					],
				],
			],
		];
	}

	/**
	 * @param array $disabled
	 * @return array
	 */
	private function getActionList(array $disabled = []): array
	{
		$list = $this->getFullList();
		if (empty($disabled))
		{
			return $list;
		}

		foreach ($list as $k => $row)
		{
			if (in_array($row['VALUE'], $disabled))
			{
				unset($list[$k]);
			}
		}

		return $list;
	}

	/**
	 * @return array
	 */
	private function getFullList(): array
	{
		$actionList = [];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_CHOOSE_ACTION'),
			'VALUE' => self::ACTION_NONE,
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction(null)"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_PING'),
			'VALUE' => self::ACTION_PING,
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('ping')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_COMPLETE'),
			'VALUE' => self::ACTION_COMPLETE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('complete')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_DEADLINE'),
			'VALUE' => self::ACTION_SET_DEADLINE,
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Types::DATE,
							'ID' => 'action_set_deadline',
							'NAME' => 'ACTION_SET_DEADLINE',
							'VALUE' => '',
							'TIME' => true,
						],
					],
				],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('setdeadline')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_RIGHT'),
			'VALUE' => self::ACTION_ADJUST_DEADLINE,
			'ONCHANGE' => $this->getMoveDeadlineConfig(),
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_LEFT'),
			'VALUE' => self::ACTION_SUBSTRACT_DEADLINE,
			'ONCHANGE' => $this->getMoveDeadlineConfig(),
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_V2'),
			'VALUE' => self::ACTION_SET_TASK_CONTROL,
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Types::DROPDOWN,
							'ID' => 'action_set_task_control',
							'NAME' => 'value',
							'ITEMS' => [
								[
									'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_YES'),
									'VALUE' => 'N',
								],
								[
									'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_NO'),
									'VALUE' => 'Y',
								],
							],
						],
					],
				],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('settaskcontrol')"],
					],
				],
			],
		];

		$roles = $this->getRoles();

		foreach ($roles as $role)
		{
			$key = $role['KEY'];

			$actionList[] = [
				'NAME' => $role['NAME'],
				'VALUE' => $role['VALUE'],
				'ONCHANGE' => [
					[
						'ACTION' => Actions::CREATE,
						'DATA' => [
							[
								'TYPE' => Types::TEXT,
								'ID' => "action_set_{$key}_text",
								'NAME' => "{$key}Text",
								'VALUE' => '',
								'SIZE' => 1,
							],
							[
								'TYPE' => Types::HIDDEN,
								'ID' => "action_set_{$key}",
								'NAME' => "{$key}Id",
								'VALUE' => '',
								'SIZE' => 1,
							],
						],
					],
					[
						'ACTION' => Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.initPopupBalloon('user','action_set_{$key}_text','action_set_{$key}');"],
						],
					],
					[
						'ACTION' => Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('changeuser')"],
						],
					],
				],
			];
		}

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MUTE'),
			'VALUE' => self::ACTION_MUTE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('mute')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_UNMUTE'),
			'VALUE' => self::ACTION_UNMUTE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('unmute')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_FAVORITE'),
			'VALUE' => self::ACTION_ADD_FAVORITE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('addtofavorite')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE'),
			'VALUE' => self::ACTION_REMOVE_FAVORITE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('removefromfavorite')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_GROUP'),
			'VALUE' => self::ACTION_SET_GROUP,
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Types::TEXT,
							'ID' => 'action_set_group_search',
							'NAME' => 'ACTION_SET_GROUP_SEARCH',
						],
						[
							'TYPE' => Types::HIDDEN,
							'ID' => 'action_set_group_id',
							'NAME' => 'groupId',
						],
					],
				],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.initPopupBalloon('group','action_set_group_search','action_set_group_id');"],
					],
				],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('setgroup')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_REMOVE'),
			'VALUE' => self::ACTION_DELETE,
			'ONCHANGE' => [
				['ACTION' => Actions::RESET_CONTROLS],
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('delete')"],
					],
				],
			],
		];

		return $actionList;
	}

	/**
	 * @return array[]
	 */
	private function getMoveDeadlineConfig(): array
	{
		return [
			[
				'ACTION' => Actions::CREATE,
				'DATA' => [
					[
						'TYPE' => Types::TEXT,
						'ID' => 'action_move_deadline_num',
						'NAME' => 'num',
						'VALUE' => '',
					],
					[
						'TYPE' => Types::DROPDOWN,
						'ID' => 'action_move_deadline_type',
						'NAME' => 'type',
						'ITEMS' => [
							[
								'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_DAY'),
								'VALUE' => 'day',
							],
							[
								'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_WEEK'),
								'VALUE' => 'week',
							],
							[
								'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_MONTH'),
								'VALUE' => 'month',
							],
						],
					],
				],
			],
			[
				'ACTION' => Actions::CALLBACK,
				'DATA' => [
					['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('changedeadline')"],
				],
			],
		];
	}

	/**
	 * @return array[]
	 */
	private function getRoles(): array
	{
		return [
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_CHANGE_RESPONSIBLE'),
				'VALUE' => self::ACTION_SET_RESPONSIBLE,
				'KEY' => 'responsible',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_CHANGE_ORIGINATOR'),
				'VALUE' => self::ACTION_SET_ORIGINATOR,
				'KEY' => 'originator',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_AUDITOR'),
				'VALUE' => self::ACTION_ADD_AUDITOR,
				'KEY' => 'auditor',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_ACCOMPLICE'),
				'VALUE' => self::ACTION_ADD_ACCOMPLICE,
				'KEY' => 'accomplice',
			],
		];
	}
}