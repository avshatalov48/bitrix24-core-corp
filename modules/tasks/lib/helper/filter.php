<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Counter\Type;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Replication\Replicator\RegularTaskReplicator;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Utility\ViewHelper;
use Bitrix\Tasks\Update\Preset;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;
use CTaskListState;

class Filter extends Common
{
	public const RESPONSIBLE_PRESET = 'filter_tasks_role_responsible';
	public const ACCOMPLICE_PRESET = 'filter_tasks_role_accomplice';
	public const ORIGINATOR_PRESET = 'filter_tasks_role_originator';
	public const AUDITOR_PRESET = 'filter_tasks_role_auditor';
	public const REGULAR_PRESET = 'filter_tasks_task_is_regular';
	public const SCRUM_PRESET = 'filter_tasks_scrum';

	protected static ?array $instance = null;
	protected static array $options = [];

	private bool $isFilterDataSet = false;
	private array $filterData = [];

	private static bool $isRolePresetsEnabledForMobile = false;

	/**
	 * @return false|mixed|string|null
	 */
	public function getDefaultRoleId()
	{
		if (static::isRolesEnabled())
		{
			return Role::ALL;
		}

		static $roleId = null;

		if (!$roleId)
		{
			$request = Context::getCurrent()->getRequest();
			if ($request->isAjaxRequest())
			{
				return false;
			}

			$filterOptions = $this->getOptions();
			$filter = $filterOptions->getFilter();

			$fState = $request->get('F_STATE');
			if ($fState && !is_array($fState) && mb_strpos($fState, 'sR') === 0)
			{
				$roleId = Role::getByState($fState);
				$currentPresetId = $filterOptions->getCurrentFilterId();
				$filterSettings = $filterOptions->getFilterSettings($currentPresetId);

				if (
					is_array($filterSettings['fields'] ?? null)
					&& (
						!array_key_exists('ROLEID', $filterSettings['fields'])
						|| !$filterSettings['fields']['ROLEID']
					)
				)
				{
					if ($roleId)
					{
						$filterSettings['additional']['ROLEID'] = $roleId;
					}
					else
					{
						unset($filterSettings['additional']['ROLEID']);
					}
				}

				$filterOptions->setFilterSettings($currentPresetId, $filterSettings, true, false);
				$filterOptions->save();
			}
			else
			{
				$roleId = ($filter['ROLEID'] ?? null);
			}
		}

		return $roleId;
	}

	/**
	 * @return Options
	 */
	public function getOptions(): Options
	{
		$filterId = $this->getId();

		if (empty(static::$options[$filterId]))
		{
			static::$options[$filterId] = new Options($filterId, $this->getAllPresets());
		}

		return static::$options[$filterId];
	}

	public function getAllPresets(): array
	{
		$presets = static::getPresets($this);
		$contexts = $this->getContexts();
		foreach ($contexts as $name)
		{
			$registryId = FilterRegistry::getId($name, $this->getGroupId());
			$options = new Options($registryId, $presets);
			$presets = array_merge($presets, $options->getOptions()['filters']);

			if (static::isRolesEnabled())
			{
				$presets = array_merge($presets, static::getRolePresets($this->isScrumProject()));
			}
		}

		return $presets;
	}

	public static function getPresets(self $filterInstance = null): array
	{
		$presets = [];
		$isScrumProject = false;
		$userId = 0;
		if ($filterInstance)
		{
			$isScrumProject = $filterInstance->isScrumProject();
			$userId = $filterInstance->getUserId();
		}

		if ($isScrumProject)
		{
			$presets[static::SCRUM_PRESET] = [
				'name' => Loc::getMessage('TASKS_PRESET_SCRUM'),
				'default' => true,
				'fields' => [
					'STATUS' => [
						Status::PENDING,
						Status::IN_PROGRESS,
						Status::SUPPOSEDLY_COMPLETED,
						Status::DEFERRED,
						EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT,
					],
					'STORY_POINTS' => '',
				],
				'sort' => 1,
			];
		}

		$presets['filter_tasks_in_progress'] = [
			'name' => Loc::getMessage('TASKS_PRESET_IN_PROGRESS'),
			'default' => !$isScrumProject,
			'fields' => [
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
			'sort' => $isScrumProject ? 2 : 1,
		];

		$presets['filter_tasks_completed'] = [
			'name' => Loc::getMessage('TASKS_PRESET_COMPLETED'),
			'default' => false,
			'fields' => [
				'STATUS' => [Status::COMPLETED],
			],
			'sort' => $isScrumProject ? 3 : 2,
		];

		if ($isScrumProject)
		{
			$presets['filter_tasks_my'] = [
				'name' => Loc::getMessage('TASKS_PRESET_MY'),
				'default' => false,
				'fields' => ['RESPONSIBLE_ID' => $userId],
				'sort' => 4,
			];
		}

		if (!$isScrumProject)
		{
			$presets['filter_tasks_deferred'] = [
				'name' => Loc::getMessage('TASKS_PRESET_DEFERRED'),
				'default' => false,
				'fields' => [
					'STATUS' => [Status::DEFERRED],
				],
				'sort' => 3,
			];
			$presets['filter_tasks_expire'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						Status::PENDING,
						Status::IN_PROGRESS,
					],
					'PROBLEM' => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
				],
				'sort' => 4,
			];
			$presets['filter_tasks_expire_candidate'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED_CAND'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						Status::PENDING,
						Status::IN_PROGRESS,
					],
					'PROBLEM' => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
				],
				'sort' => 5,
			];
		}

		return $presets;
	}

	public static function getRolePresets(bool $isScrumProject = false): array
	{
		if ($isScrumProject)
		{
			return [];
		}

		$presets[static::RESPONSIBLE_PRESET] = [
			'name' => Loc::getMessage('TASKS_PRESET_I_DO'),
			'default' => false,
			'fields' => [
				'ROLEID' => Role::RESPONSIBLE,
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
			'sort' => 6,
		];

		$presets[static::ACCOMPLICE_PRESET] = [
			'name' => Loc::getMessage('TASKS_PRESET_I_ACCOMPLICES'),
			'default' => false,
			'fields' => [
				'ROLEID' => Role::ACCOMPLICE,
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
			'sort' => 7,
		];

		$presets[static::ORIGINATOR_PRESET] = [
			'name' => Loc::getMessage('TASKS_PRESET_I_ORIGINATOR'),
			'default' => false,
			'fields' => [
				'ROLEID' => Role::ORIGINATOR,
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
			'sort' => 8,
		];

		$presets[static::AUDITOR_PRESET] = [
			'name' => Loc::getMessage('TASKS_PRESET_I_AUDITOR'),
			'default' => false,
			'fields' => [
				'ROLEID' => Role::AUDITOR,
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
			'sort' => 9,
		];

		return $presets;
	}

	public static function isRolesEnabled(): bool
	{
		return Preset::isRolePresetsEnabled() || self::$isRolePresetsEnabledForMobile;
	}

	public static function setRolePresetsEnabledForMobile(bool $enabled): void
	{
		self::$isRolePresetsEnabledForMobile = $enabled;
	}

	public static function getRegularPresets(int $startSort = 0): array
	{
		if (!RegularTaskReplicator::isEnabled())
		{
			return [];
		}

		$presets[static::REGULAR_PRESET] = [
			'name' => Loc::getMessage('TASKS_PRESET_IS_REGULAR'),
			'default' => false,
			'fields' => [
				'PARAMS' => [
					'IS_REGULAR',
					'::REMOVE-MEMBER'
				]
			],
			'sort' => $startSort + 1,
		];

		return $presets;
	}
	/**
	 * @return array
	 */
	public function process(): array
	{
		$filter = array_merge(
			$this->processMainFilter(),
			$this->processUFFilter()
		);
		$filter['CHECK_PERMISSIONS'] = 'Y';
		$filter['ONLY_ROOT_TASKS'] = 'Y';

		return $filter;
	}

	/**
	 * @return array
	 */
	private function processUFFilter(): array
	{
		$ufFilter = [];
		$fieldsToSkipEmptyClearing = [];

		$filters = $this->getFilters();
		foreach ($filters as $fieldId => $filterRow)
		{
			if (!array_key_exists('uf', $filterRow) || !$filterRow['uf'])
			{
				continue;
			}

			switch ($filterRow['type'])
			{
				case 'crm':
				case 'string':
					$ufFilter["%{$fieldId}"] = $this->getFilterFieldData($fieldId);
					break;

				case 'date':
					$data = $this->getDateFilterFieldData($filterRow);
					if ($data)
					{
						$ufFilter = array_merge($ufFilter, $data);
					}
					break;

				case 'number':
					$data = $this->getNumberFilterFieldData($filterRow);
					if ($data)
					{
						$ufFilter = array_merge($ufFilter, $data);
					}
					break;

				case 'list':
					$data = $this->getListFilterFieldData($filterRow);
					if ($data)
					{
						$map = [
							1 => null,
							2 => 1,
						];
						$key = key($data);
						$value = current($data);
						if (array_key_exists($value, $map))
						{
							$data[$key] = $map[$value];
						}
						$ufFilter = array_merge($ufFilter, $data);
						$fieldsToSkipEmptyClearing[] = $key;
					}
					break;

				case 'dest_selector':
				case 'entity_selector':
					$data = $this->getSelectorFilterFieldData($filterRow);
					if ($data)
					{
						$ufFilter = array_merge($ufFilter, $data);
					}
					break;

				default:
					break;
			}
		}

		$ufFilter = array_filter(
			$ufFilter,
			static function ($value, $key) use ($fieldsToSkipEmptyClearing) {
				return in_array($key, $fieldsToSkipEmptyClearing, true)
					|| $value === '0'
					|| !empty($value);
			},
			ARRAY_FILTER_USE_BOTH
		);

		return $ufFilter;
	}

	private function processMainFilter(): array
	{
		$filter = [];

		$this->getDefaultRoleId();

		$groupId = (int)$this->getGroupId();
		if ($groupId > 0)
		{
			$filter['GROUP_ID'] = $groupId;
		}
		else if ($this->isFilterEmpty())
		{
			$filter['::SUBFILTER-ROLEID']['MEMBER'] = $this->getUserId();

			return $filter;
		}

		if ($this->getFilterFieldData('FIND') && !FilterLimit::isLimitExceeded())
		{
			$value = SearchIndex::prepareStringToSearch($this->getFilterFieldData('FIND'));
			if ($value !== '')
			{
				$filter['::SUBFILTER-FULL_SEARCH_INDEX']['*FULL_SEARCH_INDEX'] = $value;
			}
		}

		$filters = $this->getFilters();
		foreach ($filters as $fieldId => $filterRow)
		{
			if (array_key_exists('uf', $filterRow))
			{
				continue;
			}

			$rawFilter = [];
			switch ($filterRow['type'])
			{
				default:
					if ($field = $this->getFilterFieldData($filterRow['id']))
					{
						$rawFilter[$filterRow['id']] = $field;
					}
					break;

				case 'date':
					$rawFilter = $this->getDateFilterFieldData($filterRow);
					break;

				case 'number':
					$rawFilter = $this->getNumberFilterFieldData($filterRow);
					break;

				case 'list':
					$rawFilter = $this->getListFilterFieldData($filterRow);
					break;

				case 'dest_selector':
				case 'entity_selector':
					$rawFilter = $this->getSelectorFilterFieldData($filterRow);
					break;

				case 'string':
					if ($field = $this->getFilterFieldData($filterRow['id']))
					{
						$rawFilter["%{$filterRow['id']}"] = $field;
					}
					break;
			}

			if ($rawFilter)
			{
				$filter["::SUBFILTER-{$fieldId}"] = $rawFilter;
			}
		}

		$filter = $this->postProcessMainFilter($filter);

		return $filter;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private function postProcessMainFilter(array $filter): array
	{
		$prefix = '::SUBFILTER-';
		$searchKey = "{$prefix}COMMENT_SEARCH_INDEX";
		$statusKey = "{$prefix}STATUS";

		if (isset($filter["{$prefix}PARAMS"]['::REMOVE-MEMBER']))
		{
			unset($filter["{$prefix}ROLEID"]['MEMBER'], $filter["{$prefix}PARAMS"]['::REMOVE-MEMBER']);
		}

		if (isset($filter[$searchKey]))
		{
			reset($filter[$searchKey]);

			$key = key($filter[$searchKey]);
			$value = SearchIndex::prepareStringToSearch(current($filter[$searchKey]));
			if ($value !== '')
			{
				$filter[$searchKey]["*{$key}"] = $value;
			}
			unset($filter[$searchKey][$key]);
		}

		if (
			isset($filter[$statusKey]['REAL_STATUS'])
			&& !empty($filter[$statusKey]['REAL_STATUS'])
			&& !in_array(Status::COMPLETED, $filter[$statusKey]['REAL_STATUS'])
			&& (int)$this->getUserId() === User::getId()
		)
		{
			$filter[$statusKey] = [
				'::LOGIC' => 'OR',
				'::SUBFILTER-1' => $filter[$statusKey],
				'::SUBFILTER-2' => [
					'WITH_COMMENT_COUNTERS' => 'Y',
					'REAL_STATUS' => Status::COMPLETED,
				],
			];
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	public function getFilters(): array
	{
		static $filters = [];

		if (empty($filters))
		{
			$filters = $this->getFilterRaw();
		}

		return $filters;
	}

	/**
	 * @return array
	 */
	private function getFilterRaw(): array
	{
		$filter = [];
		$fields = $this->getAvailableFields();

		$isScrumProject = $this->isScrumProject();

		if (in_array('CREATED_BY', $fields))
		{
			$filter['CREATED_BY'] = [
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_CREATED_BY'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'TASKS_FILTER_CREATED_BY',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false
								],
							],
						]
					],
				],
			];
		}

		if (in_array('RESPONSIBLE_ID', $fields))
		{
			$filter['RESPONSIBLE_ID'] = [
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_ASSIGNEE_ID'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'TASKS_FILTER_RESPONSIBLE_ID',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false
								],
							],
						]
					],
				],
				'default' => $isScrumProject,
			];
		}

		if (in_array('STATUS', $fields))
		{
			$statusItems = [
				Status::PENDING => Loc::getMessage('TASKS_STATUS_2'),
				Status::IN_PROGRESS => Loc::getMessage('TASKS_STATUS_3'),
				Status::SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_STATUS_4'),
				Status::COMPLETED => Loc::getMessage('TASKS_STATUS_5'),
				Status::DEFERRED => Loc::getMessage('TASKS_STATUS_6'),
				EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT => Loc::getMessage('TASKS_STATUS_8'),
			];

			$filter['STATUS'] = [
				'id' => 'STATUS',
				'name' => Loc::getMessage('TASKS_FILTER_STATUS'),
				'type' => 'list',
				'params' => ['multiple' => 'Y'],
				'items' => $statusItems,
				'default' => $isScrumProject,
			];
		}

		if (in_array('DEADLINE', $fields))
		{
			$filter['DEADLINE'] = [
				'id' => 'DEADLINE',
				'name' => Loc::getMessage('TASKS_FILTER_DEADLINE'),
				'type' => 'date',
			];
		}

		if (in_array('GROUP_ID', $fields))
		{
			$filter['GROUP_ID'] = [
				'id' => 'GROUP_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_GROUP'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'TASKS_FILTER_GROUP_ID',
						'entities' => [
							[
								'id' => 'project',
								'options' => [
									'dynamicLoad' => true,
									'dynamicSearch' => true,
								],
							],
						]
					],
				]
			];
		}

		if (in_array('PROBLEM', $fields))
		{
			$filter['PROBLEM'] = [
				'id' => 'PROBLEM',
				'name' => Loc::getMessage('TASKS_FILTER_PROBLEM'),
				'type' => 'list',
				'items' => ($isScrumProject ? $this->getAllowedTaskScrumCategories() : $this->getAllowedTaskCategories()),
			];
		}

		if (in_array('PARAMS', $fields))
		{
			$filter['PARAMS'] = [
				'id' => 'PARAMS',
				'name' => Loc::getMessage('TASKS_FILTER_PARAMS'),
				'type' => 'list',
				'params' => ['multiple' => 'Y'],
				'items' => [
					'MARKED' => Loc::getMessage('TASKS_FILTER_PARAMS_MARKED'),
					'IN_REPORT' => Loc::getMessage('TASKS_FILTER_PARAMS_IN_REPORT'),
					'OVERDUED' => Loc::getMessage('TASKS_FILTER_PARAMS_OVERDUED'),
					'FAVORITE' => Loc::getMessage('TASKS_FILTER_PARAMS_FAVORITE'),
					'ANY_TASK' => Loc::getMessage('TASKS_FILTER_PARAMS_ANY_TASK'),
					// 'IS_REGULAR' => Loc::getMessage('TASKS_FILTER_PARAMS_IS_REGULAR'),
				],
			];
		}

		if (in_array('ID', $fields))
		{
			$filter['ID'] = [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_FILTER_ID'),
				'type' => 'number',
			];
		}
		if (in_array('TITLE', $fields))
		{
			$filter['TITLE'] = [
				'id' => 'TITLE',
				'name' => Loc::getMessage('TASKS_FILTER_TITLE'),
				'type' => 'string',
			];
		}
		if (in_array('PRIORITY', $fields))
		{
			$filter['PRIORITY'] = [
				'id' => 'PRIORITY',
				'name' => Loc::getMessage('TASKS_PRIORITY'),
				'type' => 'list',
				'items' => [
					1 => Loc::getMessage('TASKS_PRIORITY_1'),
					2 => Loc::getMessage('TASKS_PRIORITY_2'),
				],
			];
		}
		if (in_array('MARK', $fields))
		{
			$filter['MARK'] = [
				'id' => 'MARK',
				'name' => Loc::getMessage('TASKS_FILTER_MARK_MSGVER_1'),
				'type' => 'list',
				'items' => [
					'P' => Loc::getMessage('TASKS_MARK_P'),
					'N' => Loc::getMessage('TASKS_MARK_N'),
				],
			];
		}
		if (in_array('ALLOW_TIME_TRACKING', $fields))
		{
			$filter['ALLOW_TIME_TRACKING'] = [
				'id' => 'ALLOW_TIME_TRACKING',
				'name' => Loc::getMessage('TASKS_FILTER_ALLOW_TIME_TRACKING'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_Y'),
					'N' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_N'),
				],
			];
		}
		if (in_array('CREATED_DATE', $fields))
		{
			$filter['CREATED_DATE'] = [
				'id' => 'CREATED_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_CREATED_DATE'),
				'type' => 'date',
			];
		}
		if (in_array('CLOSED_DATE', $fields))
		{
			$filter['CLOSED_DATE'] = [
				'id' => 'CLOSED_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_CLOSED_DATE'),
				'type' => 'date',
			];
		}
		if (in_array('DATE_START', $fields))
		{
			$filter['DATE_START'] = [
				'id' => 'DATE_START',
				'name' => Loc::getMessage('TASKS_FILTER_DATE_START'),
				'type' => 'date',
			];
		}
		if (in_array('START_DATE_PLAN', $fields))
		{
			$filter['START_DATE_PLAN'] = [
				'id' => 'START_DATE_PLAN',
				'name' => Loc::getMessage('TASKS_FILTER_START_DATE_PLAN'),
				'type' => 'date',
			];
		}
		if (in_array('END_DATE_PLAN', $fields))
		{
			$filter['END_DATE_PLAN'] = [
				'id' => 'END_DATE_PLAN',
				'name' => Loc::getMessage('TASKS_FILTER_END_DATE_PLAN'),
				'type' => 'date',
			];
		}

		if (in_array('ACTIVE', $fields))
		{
			$filter['ACTIVE'] = [
				'id' => 'ACTIVE',
				'name' => Loc::getMessage('TASKS_FILTER_ACTIVE'),
				'type' => 'date',
			];
		}

		if (in_array('ACCOMPLICE', $fields))
		{
			$filter['ACCOMPLICE'] = [
				'id' => 'ACCOMPLICE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_ACCOMPLICES'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'TASKS_FILTER_ACCOMPLICE',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false
								],
							],
						]
					],
				]
			];
		}
		if (in_array('AUDITOR', $fields))
		{
			$filter['AUDITOR'] = [
				'id' => 'AUDITOR',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_AUDITOR'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'TASKS_FILTER_AUDITOR',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false
								],
							],
						]
					],
				]
			];
		}

		if (in_array('TAG', $fields))
		{
			$filter['TAG'] = [
				'id' => 'TAG',
				'name' => Loc::getMessage('TASKS_FILTER_TAG'),
				'type' => 'entity_selector',
				'default' => $isScrumProject,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => ($isScrumProject ? "TASKS_SCRUM_TAG_{$this->getGroupId()}" : 'TASKS_TAG'),
						'entities' => [
							[
								'id' => 'task-tag',
								'options' => $isScrumProject
									? [
										'groupId' => $this->getGroupId(),
										'filter' => true,
									]
									: ['filter' => true]
								,
							],
						],
						'dropdownMode' => true,
						'compactView' => true,
					],
				],
			];
		}

		if (FlowFeature::isOn())
		{
			$filter['FLOW'] = [
				'id' => 'FLOW',
				'name' => Loc::getMessage('TASKS_FILTER_FLOW'),
				'type' => 'entity_selector',
				'params' => [
					'dialogOptions' => [
						'entities' => [
							[
								'id' => 'flow',
								'options' => ['filter' => true],
							],
						],
						'dropdownMode' => true,
						'compactView' => true,
					],
				],
			];
		}

		if ($isScrumProject)
		{
			$filter['STORY_POINTS'] = [
				'id' => 'STORY_POINTS',
				'name' => Loc::getMessage('TASKS_FILTER_STORY_POINTS'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_FILTER_STORY_POINTS_Y'),
					'N' => Loc::getMessage('TASKS_FILTER_STORY_POINTS_N'),
				],
				'default' => true,
			];

			$filter['EPIC'] = [
				'id' => 'EPIC',
				'name' => Loc::getMessage('TASKS_FILTER_EPIC'),
				'type' => 'list',
				'items' => $this->getEpics(),
				'default' => true,
			];
		}

		if (in_array('ROLEID', $fields))
		{
			$items = [];
			foreach (Role::getRoles() as $roleCode => $roleName)
			{
				$items[$roleCode] = $roleName['TITLE'];
			}
			$filter['ROLEID'] = [
				'id' => 'ROLEID',
				'name' => Loc::getMessage('TASKS_FILTER_ROLEID'),
				'type' => 'list',
				'default' => !$isScrumProject,
				'items' => $items,
			];
		}

		if (in_array('COMMENT', $fields))
		{
			$filter['COMMENT_SEARCH_INDEX'] = [
				'id' => 'COMMENT_SEARCH_INDEX',
				'name' => Loc::getMessage('TASKS_FILTER_COMMENT'),
				'type' => 'fulltext',
			];
		}

		if (in_array('CREATED_DATE', $fields))
		{
			$filter['ACTIVITY_DATE'] = [
				'id' => 'ACTIVITY_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_ACTIVITY_DATE'),
				'type' => 'date',
			];
		}

		if (in_array('STAGE_ID', $fields))
		{
			$items = $isScrumProject ? $this->getScrumStages() : $this->getGroupStages();

			if (!empty($items))
			{
				$filter['STAGE_ID'] = [
					'id' => 'STAGE_ID',
					'name' => Loc::getMessage('TASKS_FILTER_STAGE_ID'),
					'type' => 'list',
					'params' => ['multiple' => 'Y'],
					'default' => true,
					'items' => $items,
				];
			}
		}

		if (!empty($uf = $this->getUF()))
		{
			foreach ($uf as $item)
			{
				$type = $item['USER_TYPE_ID'];
				if ($type === 'crm')
				{
					continue;
				}

				$availableTypes = ['datetime', 'string', 'double', 'boolean'];
				if (!in_array($type, $availableTypes, true))
				{
					$type = 'string';
				}

				if ($type === 'datetime')
				{
					$type = 'date';
				}
				else if ($type === 'double')
				{
					$type = 'number';
				}

				if ($type === 'boolean')
				{
					$filter[$item['FIELD_NAME']] = [
						'id' => $item['FIELD_NAME'],
						'name' => $item['EDIT_FORM_LABEL'],
						'type' => 'list',
						'items' => [
							1 => GetMessage('TASKS_FILTER_NO'),
							2 => GetMessage('TASKS_FILTER_YES'),
						],
						'uf' => true,
					];
				}
				else
				{
					$filter[$item['FIELD_NAME']] = [
						'id' => $item['FIELD_NAME'],
						'name' => $item['EDIT_FORM_LABEL'],
						'type' => $type,
						'uf' => true,
					];
				}
			}
		}

		return $filter;
	}

	/**
	 * Get available fields in filter.
	 * @return array
	 */
	public function getAvailableFields(): array
	{
		$fields = [
			'ID',
			'TITLE',
			'STATUS',
			'PROBLEM',
			'PARAMS',
			'PRIORITY',
			'MARK',
			'ALLOW_TIME_TRACKING',
			'DEADLINE',
			'CREATED_DATE',
			'CLOSED_DATE',
			'DATE_START',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'RESPONSIBLE_ID',
			'CREATED_BY',
			'ACCOMPLICE',
			'AUDITOR',
			'TAG',
			'ACTIVE',
			'ROLEID',
			'COMMENT',
			'ACTIVITY_DATE',
			'FLOW',
		];

		if ((int)$this->getGroupId() > 0)
		{
			$fields[] = 'STAGE_ID';
		}
		else
		{
			$fields[] = 'GROUP_ID';
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	private function getAllowedTaskCategories(): array
	{
		$list = [];

		$taskCategories = [
			CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
			CTaskListState::VIEW_TASK_CATEGORY_NEW,
			CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
			CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
			CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL,
			CTaskListState::VIEW_TASK_CATEGORY_DEFERRED,
			CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		];
		if ($this->getGroupId() > 0)
		{
			$taskCategories[] = CTaskListState::VIEW_TASK_CATEGORY_PROJECT_EXPIRED;
			$taskCategories[] = CTaskListState::VIEW_TASK_CATEGORY_PROJECT_NEW_COMMENTS;
		}
		foreach ($taskCategories as $categoryId)
		{
			$list[$categoryId] = CTaskListState::getTaskCategoryName($categoryId);
		}

		return $list;
	}

	private function getAllowedTaskScrumCategories(): array
	{
		$list = [];

		$taskCategories = [
			CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		];
		if ($this->getGroupId() > 0)
		{
			$taskCategories[] = CTaskListState::VIEW_TASK_CATEGORY_PROJECT_NEW_COMMENTS;
		}
		foreach ($taskCategories as $categoryId)
		{
			$list[$categoryId] = CTaskListState::getTaskCategoryName($categoryId);
		}

		return $list;
	}

	/**
	 * The method checks if the current filter is more accurate than presets.
	 *
	 * @return bool
	 */
	public function isExactSearchApplied(): bool
	{
		$options = $this->getOptions();

		$currentPresetId = $options->getCurrentFilterId();

		$additionalPresetFields = $options->getAdditionalPresetFields($currentPresetId);

		$filledTmpPreset = false;
		if ($currentPresetId === Options::TMP_FILTER)
		{
			$ignoreValues = ['', 'undefined', 'tmp_filter'];
			$filterData = $this->getFilterData();
			foreach($filterData as $fieldValue)
			{
				if (is_array($fieldValue) && !empty($fieldValue))
				{
					$filledTmpPreset = true;
				}
				elseif (is_string($fieldValue) && !in_array($fieldValue, $ignoreValues, true))
				{
					$filledTmpPreset = true;
				}
			}
		}

		return (
			!$this->isFilterEmpty()
			&& (
				$this->getFilterFieldData('FIND', '')
				|| (!empty($additionalPresetFields) || $filledTmpPreset)
			)
		);
	}

	private function isFilterEmpty(): bool
	{
		if ($this->isFilterDataSet)
		{
			return empty($this->filterData);
		}

		return !$this->getFilterFieldData('FILTER_APPLIED', false);
	}

	/**
	 * @param $field
	 * @param $default
	 * @return mixed
	 */
	private function getFilterFieldData($field, $default = null): mixed
	{
		$filterData = $this->getFilterData();

		return (array_key_exists($field, $filterData) ? $filterData[$field] : $default);
	}

	/**
	 * @return array
	 */
	private function getFilterData(): array
	{
		if ($this->isFilterDataSet)
		{
			return $this->filterData;
		}

		return $this->getOptions()->getFilter($this->getFilters());
	}

	public function setFilterData(array $filterData): void
	{
		$this->filterData = $filterData;
		$this->isFilterDataSet = true;
	}

	/**
	 * @param $row
	 * @return array
	 */
	private function getDateFilterFieldData($row): array
	{
		$filter = [];

		$rowId = $row['id'];
		$from = $this->getFilterFieldData("{$rowId}_from");
		$to = $this->getFilterFieldData("{$rowId}_to");

		if ($rowId === 'ACTIVE' && !empty($from))
		{
			$filter['ACTIVE']['START'] = $from;
			$filter['ACTIVE']['END'] = $to;

			return $filter;
		}

		if ($from)
		{
			$filter[">={$rowId}"] = $from;
		}

		if ($to)
		{
			$filter["<={$rowId}"] = $to;
		}

		return $filter;
	}

	/**
	 * @param $row
	 * @return array
	 */
	private function getNumberFilterFieldData($row): array
	{
		$filter = [];

		$rowId = $row['id'];
		$equalSign = ($this->getFilterFieldData("{$rowId}_numsel") === 'range' ? '=' : '');

		$from = $this->getFilterFieldData("{$rowId}_from");
		if ($from || $from === '0')
		{
			$filter[">{$equalSign}{$rowId}"] = $from;
		}

		$to = $this->getFilterFieldData("{$rowId}_to");
		if ($to || $to === '0')
		{
			$filter["<{$equalSign}{$rowId}"] = $to;
		}

		if (isset($from, $to) && $from == $to) // values of type double may be here
		{
			unset(
				$filter[">{$equalSign}{$rowId}"],
				$filter["<{$equalSign}{$rowId}"]
			);
			$filter[$rowId] = $from;
		}

		return $filter;
	}

	/**
	 * @param $row
	 * @return array
	 */
	private function getListFilterFieldData($row): array
	{
		$filter = [];

		$rowId = $row['id'];
		$field = $this->getFilterFieldData($rowId, []);

		switch ($rowId)
		{
			case 'STATUS':
				if (!empty($field))
				{
					$filter['REAL_STATUS'] = $field;
				}
				break;

			case 'PROBLEM':
				switch ($field)
				{
					case Type::TYPE_WO_DEADLINE:
						$filter['DEADLINE'] = '';
						break;

					case Type::TYPE_EXPIRED:
						if ($this->getGroupId() > 0)
						{
							$filter['MEMBER'] = $this->getUserId();
						}
						$filter['<=DEADLINE'] = Deadline::getExpiredTime();
						$filter['IS_MUTED'] = 'N';
						$filter['REAL_STATUS'] = [Status::PENDING, Status::IN_PROGRESS];
						break;

					case Type::TYPE_EXPIRED_CANDIDATES:
						$filter['>=DEADLINE'] = Deadline::getExpiredTime();
						$filter['<=DEADLINE'] = Deadline::getExpiredSoonTime();
						break;

					case Type::TYPE_WAIT_CTRL:
						$filter['REAL_STATUS'] = Status::SUPPOSEDLY_COMPLETED;
						$filter['!RESPONSIBLE_ID'] = $this->getUserId();
						$filter['=CREATED_BY'] = $this->getUserId();
						break;

					case Type::TYPE_NEW:
						$filter['VIEWED'] = 0;
						$filter['VIEWED_BY'] = $this->getUserId();
						break;

					case Type::TYPE_DEFERRED:
						$filter['REAL_STATUS'] = Status::DEFERRED;
						break;

					case Type::TYPE_NEW_COMMENTS:
						if ($this->getGroupId() > 0)
						{
							$filter['MEMBER'] = $this->getUserId();
						}
						$filter['WITH_NEW_COMMENTS'] = 'Y';
						$filter['IS_MUTED'] = 'N';
						break;

					case Type::TYPE_PROJECT_EXPIRED:
						$filter['PROJECT_EXPIRED'] = 'Y';
						break;

					case Type::TYPE_PROJECT_NEW_COMMENTS:
						$filter['PROJECT_NEW_COMMENTS'] = 'Y';
						break;

					default:
						break;
				}
				break;

			case 'ROLEID':
				switch ($field)
				{
					case Role::RESPONSIBLE:
						$filter['=RESPONSIBLE_ID'] = $this->getUserId();
						break;

					case Role::ORIGINATOR:
						$filter['=CREATED_BY'] = $this->getUserId();
						$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
						break;

					case Role::ACCOMPLICE:
						$filter['=ACCOMPLICE'] = $this->getUserId();
						break;

					case Role::AUDITOR:
						$filter['=AUDITOR'] = $this->getUserId();
						break;

					default:
						if (!$this->getGroupId())
						{
							$filter['MEMBER'] = $this->getUserId();
						}
						break;
				}
				break;

			case 'PARAMS':
				if ($field)
				{
					foreach ($field as $item)
					{
						switch ($item)
						{
							case 'FAVORITE':
								$filter['FAVORITE'] = 'Y';
								break;

							case 'MARKED':
								$filter['!MARK'] = false;
								break;

							case 'OVERDUED':
								$filter['OVERDUED'] = 'Y';
								break;

							case 'IN_REPORT':
								$filter['ADD_IN_REPORT'] = 'Y';
								break;
							case 'IS_REGULAR':
								$filter['IS_REGULAR'] = 'Y';
								$filter['::REMOVE-MEMBER'] = true; // hack
								break;

							case 'SUBORDINATE':
								// Don't set SUBORDINATE_TASKS for admin, it will cause all tasks to be showed
								if (!User::isSuper())
								{
									$filter['SUBORDINATE_TASKS'] = 'Y';
								}
								break;

							case 'ANY_TASK':
								$filter['::REMOVE-MEMBER'] = true; // hack
								break;
						}
					}
				}
				break;
			case 'STAGE_ID':
				if (!empty($field) && is_array($field))
				{
					$firstStage = current(array_keys($row['items']));
					if (in_array($firstStage, $field))
					{
						$field[] = '0';
					}

					$filter[$rowId] = $field;
				}
				break;
			default:
				if ($field)
				{
					$filter[$rowId] = $field;
				}
				break;
		}

		return $filter;
	}

	/**
	 * @param $row
	 * @return array
	 */
	private function getSelectorFilterFieldData($row): array
	{
		$filter = [];

		$rowId = $row['id'];
		$value = $this->getFilterFieldData($rowId);

		if (!empty($value))
		{
			$filter[$rowId] = $value;
		}

		return $filter;
	}

	/**
	 * @return mixed
	 */
	public function getDefaultPresetKey()
	{
		return $this->getOptions()->getDefaultFilterId();
	}

	/**
	 * @return UserField|array|null|string
	 */
	private function getUF()
	{
		$uf = Task::getUserFieldControllerClass();

		$scheme = $uf::getScheme();
		unset($scheme['UF_TASK_WEBDAV_FILES'], $scheme['UF_MAIL_MESSAGE']);

		return $scheme;
	}

	private function getEpics(): array
	{
		$epicService = new EpicService();

		$epics = [];
		foreach ($epicService->getEpics($this->getGroupId()) as $epic)
		{
			$epics[$epic['id']] = $epic['name'];
		}

		return $epics;
	}

	private function getContexts(): array
	{
		return match (true)
		{
			$this->isGantt() => [FilterRegistry::FILTER_GANTT],
			$this->isGrid() => [FilterRegistry::FILTER_GRID],
			default => FilterRegistry::getList(),
		};
	}

	private function getScrumStages(): array
	{
		$items = [];

		$viewHelper = new ViewHelper();
		$activeTab = $viewHelper->getActiveView($this->groupId);

		if ($activeTab === ViewHelper::VIEW_ACTIVE_SPRINT)
		{
			$sprintService = new SprintService();
			$sprint = $sprintService->getActiveSprintByGroupId($this->groupId);
			if ($sprint->isActiveSprint())
			{
				foreach (StagesTable::getActiveSprintStages($sprint->getId(), true) as $stage)
				{
					$items[(int)$stage['ID']] = $stage['TITLE'];
				}
			}
		}

		return $items;
	}

	private function getGroupStages(): array
	{
		$items = [];

		foreach (StagesTable::getGroupStages($this->groupId, true) as $stage)
		{
			$items[(int)$stage['ID']] = $stage['TITLE'];
		}

		return $items;
	}
}