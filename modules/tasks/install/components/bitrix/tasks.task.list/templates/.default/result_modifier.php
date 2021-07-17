<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Tasks\Grid\Task;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

Extension::load(["ui.notification", "ui.icons"]);

if (Main\ModuleManager::isModuleInstalled('rest'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		[
			'PLACEMENT' => 'TASK_LIST_CONTEXT_MENU',
			'PLACEMENT_OPTIONS' => [],
			//'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
			'MENU_EVENT_MODULE' => 'tasks',
			'MENU_EVENT' => 'onTasksBuildContextMenu',
		],
		null,
		['HIDE_ICONS' => 'Y']
	);
}

CJSCore::Init(['tasks_util_query', 'task_popups']);

//region TITLE

if ($arParams['PROJECT_VIEW'] === 'Y')
{
	$title = $shortTitle = Loc::getMessage('TASKS_TITLE_PROJECT');
}
elseif ($arParams['GROUP_ID'] > 0)
{
	$title = $shortTitle = Loc::getMessage('TASKS_TITLE_GROUP_TASKS');
}
elseif ((int)$arParams['USER_ID'] === User::getId())
{
	$title = $shortTitle = Loc::getMessage('TASKS_TITLE_MY');
}
else
{
	$shortTitle = Loc::getMessage('TASKS_TITLE');
	$title = CUser::FormatName($arParams['NAME_TEMPLATE'], $arResult['USER'], true, false).": ".$shortTitle;
}

$APPLICATION->SetPageProperty('title', $title);
$APPLICATION->SetTitle($shortTitle);

if (isset($arParams['SET_NAVCHAIN']) && $arParams['SET_NAVCHAIN'] !== 'N')
{
	$APPLICATION->AddChainItem(Loc::getMessage('TASKS_TITLE'));
}

//endregion TITLE

if (!function_exists('formatDateFieldsForOutput'))
{
	/**
	 * @param $row
	 * @throws Main\ObjectException
	 */
	function formatDateFieldsForOutput(&$row): void
	{
		$dateFields = array_filter(
			CTasks::getFieldsInfo(),
			static function ($item) {
				return ($item['type'] === 'datetime' ? $item : null);
			}
		);

		$localOffset = (new \DateTime())->getOffset();
		$userOffset = CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (is_string($row[$fieldName]) && $row[$fieldName])
			{
				$date = new DateTime($row[$fieldName]);
				if ($date)
				{
					$newOffset = ($offset > 0? '+' : '') . UI::formatTimeAmount($offset, 'HH:MI');
					$row[$fieldName] = mb_substr($date->format('c'), 0, -6).$newOffset;
				}
			}
		}
	}
}

if (!function_exists('prepareTaskGroupActions'))
{
	/**
	 * @param $arResult
	 * @param $arParams
	 * @return array[][][]
	 */
	function prepareTaskGroupActions($arResult, $arParams): array
	{
		$gridId = $arParams['GRID_ID'];
		$snippet = new Grid\Panel\Snippet();

		return [
			'GROUPS' => [
				[
					'ITEMS' => [
						[
							'ID' => "action_button_{$gridId}",
							'NAME' => "action_button_{$gridId}",
							'TYPE' => Grid\Panel\Types::DROPDOWN,
							'ITEMS' => prepareGroupActionItems($arResult, $arParams),
						],
						$snippet->getApplyButton([
							'ONCHANGE' => [
								[
									'ACTION' => Grid\Panel\Actions::CALLBACK,
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
}

if (!function_exists('prepareGroupActionItems'))
{
	/**
	 * @param $arResult
	 * @param $arParams
	 * @return array[]
	 */
	function prepareGroupActionItems($arResult, $arParams): array
	{
		$actionList = [
			[
				'NAME' => Loc::getMessage('TASKS_LIST_CHOOSE_ACTION'),
				'VALUE' => 'none',
				'ONCHANGE' => [
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction(null)"],
						],
					],
				],
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_PING'),
				'VALUE' => 'ping',
				'ONCHANGE' => [
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('ping')"],
						],
					],
				],
			],
		];

		if ($arParams['SCRUM_BACKLOG'] == 'Y')
		{
			$actionList[] = [
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SPRINT'),
				'VALUE' => 'setsprint',
				'ONCHANGE' => [
					[
						'ACTION' => Grid\Panel\Actions::CREATE,
						'DATA' => [
							[
								'TYPE' => Grid\Panel\Types::CUSTOM,
								'ID' => 'action_set_sprint_title',
								'NAME' => 'ACTION_SET_SPRINT_TITLE',
								'VALUE' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SPRINT_TITLE'),
							],
							[
								'TYPE' => Grid\Panel\Types::DATE,
								'ID' => 'action_set_sprint',
								'NAME' => 'ACTION_SET_SPRINT',
								'VALUE' => '',
								'TIME' => true,
							],
						],
					],
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('setsprint')"],
						],
					],
				],
			];
		}

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_COMPLETE'),
			'VALUE' => 'complete',
			'ONCHANGE' => [
				['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('complete')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_DEADLINE'),
			'VALUE' => 'setdeadline',
			'ONCHANGE' => [
				[
					'ACTION' => Grid\Panel\Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Grid\Panel\Types::DATE,
							'ID' => 'action_set_deadline',
							'NAME' => 'ACTION_SET_DEADLINE',
							'VALUE' => '',
							'TIME' => true,
						],
					],
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('setdeadline')"],
					],
				],
			],
		];

		$moveDeadlineActionOnChange = [
			[
				'ACTION' => Grid\Panel\Actions::CREATE,
				'DATA' => [
					[
						'TYPE' => Grid\Panel\Types::TEXT,
						'ID' => 'action_move_deadline_num',
						'NAME' => 'num',
						'VALUE' => '',
					],
					[
						'TYPE' => Grid\Panel\Types::DROPDOWN,
						'ID' => 'action_move_deadline_type',
						'NAME' => 'type',
						'ITEMS' => [
							[
								'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_DAY'),
								'VALUE' => 'day',
							],
							[
								'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_WEEK'),
								'VALUE' => 'week',
							],
							[
								'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_MONTH'),
								'VALUE' => 'month',
							],
						],
					],
				],
			],
			[
				'ACTION' => Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('changedeadline')"],
				],
			],
		];

		$actionList[] = [
			'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_RIGHT'),
			'VALUE' => 'adjustdeadline',
			'ONCHANGE' => $moveDeadlineActionOnChange,
		];

		$actionList[] = [
			'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_LEFT'),
			'VALUE' => 'substractdeadline',
			'ONCHANGE' => $moveDeadlineActionOnChange,
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL'),
			'VALUE' => 'settaskcontrol',
			'ONCHANGE' => [
				[
					'ACTION' => Grid\Panel\Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_set_task_control',
							'NAME' => 'value',
							'ITEMS' => [
								[
									'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_YES'),
									'VALUE' => 'Y',
								],
								[
									'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_NO'),
									'VALUE' => 'N',
								],
							],
						],
					],
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('settaskcontrol')"],
					],
				],
			],
		];

		$roles = [
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_CHANGE_RESPONSIBLE'),
				'VALUE' => 'setresponsible',
				'KEY' => 'responsible',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_CHANGE_ORIGINATOR'),
				'VALUE' => 'setoriginator',
				'KEY' => 'originator',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_AUDITOR'),
				'VALUE' => 'addauditor',
				'KEY' => 'auditor',
			],
			[
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_ACCOMPLICE'),
				'VALUE' => 'addaccomplice',
				'KEY' => 'accomplice',
			],
		];

		foreach ($roles as $role)
		{
			$key = $role['KEY'];

			$actionList[] = [
				'NAME' => $role['NAME'],
				'VALUE' => $role['VALUE'],
				'ONCHANGE' => [
					[
						'ACTION' => Grid\Panel\Actions::CREATE,
						'DATA' => [
							[
								'TYPE' => Grid\Panel\Types::TEXT,
								'ID' => "action_set_{$key}_text",
								'NAME' => "{$key}Text",
								'VALUE' => '',
								'SIZE' => 1,
							],
							[
								'TYPE' => Grid\Panel\Types::HIDDEN,
								'ID' => "action_set_{$key}",
								'NAME' => "{$key}Id",
								'VALUE' => '',
								'SIZE' => 1,
							],
						],
					],
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.initPopupBalloon('user','action_set_{$key}_text','action_set_{$key}');"],
						],
					],
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('changeuser')"],
						],
					],
				],
			];
		}

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_MUTE'),
			'VALUE' => 'mute',
			'ONCHANGE' => [
				['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('mute')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_UNMUTE'),
			'VALUE' => 'unmute',
			'ONCHANGE' => [
				['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('unmute')"],
					],
				],
			],
		];

		if (
			$arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] !== 'FAVORITE'
			|| $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] !== 'VIEW_SECTION_ADVANCED_FILTER'
		)
		{
			$actionList[] = [
				'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_ADD_FAVORITE'),
				'VALUE' => 'addtofavorite',
				'ONCHANGE' => [
					['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
					[
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('addtofavorite')"],
						],
					],
				],
			];
		}

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE'),
			'VALUE' => 'removefromfavorite',
			'ONCHANGE' => [
				['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('removefromfavorite')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_SET_GROUP'),
			'VALUE' => 'setgroup',
			'ONCHANGE' => [
				[
					'ACTION' => Grid\Panel\Actions::CREATE,
					'DATA' => [
						[
							'TYPE' => Grid\Panel\Types::TEXT,
							'ID' => 'action_set_group_search',
							'NAME' => 'ACTION_SET_GROUP_SEARCH',
						],
						[
							'TYPE' => Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_group_id',
							'NAME' => 'groupId',
						],
					],
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.initPopupBalloon('group','action_set_group_search','action_set_group_id');"],
					],
				],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('setgroup')"],
					],
				],
			],
		];

		$actionList[] = [
			'NAME' => Loc::getMessage('TASKS_LIST_GROUP_ACTION_REMOVE'),
			'VALUE' => 'delete',
			'ONCHANGE' => [
				['ACTION' => Grid\Panel\Actions::RESET_CONTROLS],
				[
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						['JS' => "BX.Tasks.GridActions.setCurrentGroupAction('delete')"],
					],
				],
			],
		];

		return $actionList;
	}
}

$grid = new Bitrix\Tasks\Grid\Task\Grid($arResult['LIST'], $arParams);

$arResult['HEADERS'] = $grid->prepareHeaders();
$arResult['TEMPLATE_DATA'] = [
	'EXTENSION_ID' => 'tasks_task_list_component_ext_'.md5($this->GetFolder()),
];
$arResult['ROWS'] = [];
$arResult['EXPORT_LIST'] = $arResult['LIST'];

if (!empty($arResult['LIST']))
{
	$prevGroupId = 0;

	$users = [];
	$groups = [];

	foreach ($arResult['LIST'] as $row)
	{
		$users[] = $row['CREATED_BY'];
		$users[] = $row['RESPONSIBLE_ID'];

		if ($arResult['GROUP_BY_PROJECT'] && ($groupId = (int)$row['GROUP_ID']))
		{
			$groups[$groupId] = $groupId;
		}
	}

	$groups = SocialNetwork\Group::getData($groups);
	$preparedRows = $grid->prepareRows();

	foreach ($arResult['LIST'] as $key => $row)
	{
		$taskId = (int)$row['ID'];
		$groupId = (int)$row['GROUP_ID'];

		if ($arResult['GROUP_BY_PROJECT'] && $groupId !== $prevGroupId)
		{
			$groupName = htmlspecialcharsbx($groups[$groupId]['NAME']);
			$groupUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_GROUP'], ['group_id' => $groupId]);

			$actionCreateTask = SocialNetwork\Group::ACTION_CREATE_TASKS;
			$actionEditTask = SocialNetwork\Group::ACTION_EDIT_TASKS;

			$arResult['ROWS'][] = [
				'id' => 'group_'.$groupId,
				'group_id' => $groupId,
				'parent_id' => 0,
				'has_child' => true,
				'not_count' => true,
				'draggable' => false,
				'custom' => "<div class='tasks-grid-wrapper'><a href='{$groupUrl}' class='tasks-grid-group-link'>{$groupName}</a></div>",
				'attrs' => [
					'data-type' => 'group',
					'data-group-id' => $groupId,
					'data-can-create-tasks' => (SocialNetwork\Group::can($groupId, $actionCreateTask) ? 'true' : 'false'),
					'data-can-edit-tasks' => (SocialNetwork\Group::can($groupId, $actionEditTask) ? 'true' : 'false'),
				],
			];
		}

		$preparedRow = $preparedRows[$key];

		$arResult['ROWS'][] = [
			'id' => $taskId,
			'has_child' => array_key_exists($taskId, $arResult['SUB_TASK_COUNTERS']),
			'parent_id' => (Grid\Context::isInternalRequest() ? $row['PARENT_ID'] : 0),
			'parent_group_id' => $groupId,
			'columns' => $preparedRow['content'],
			'actions' => $preparedRow['actions'],
			'cellActions' => $preparedRow['cellActions'],
			'counters' => $preparedRow['counters'],
			'attrs' => [
				'data-type' => 'task',
				'data-group-id' => $groupId,
				'data-can-edit' => ($row['ACTION']['EDIT'] === true ? 'true' : 'false'),
			],
		];

		formatDateFieldsForOutput($arResult['LIST'][$key]);

		$prevGroupId = $groupId;
	}
}

$arResult['LIST'] = Bitrix\Main\Engine\Response\Converter::toJson()->process($arResult['LIST']);
$arResult['GROUP_ACTIONS'] = prepareTaskGroupActions($arResult, $arParams);
