<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;

class Filter extends Common
{
	protected static $instance;
	protected static array $options = [];

	private bool $isFilterDataSet = false;
	private array $filterData = [];

	/**
	 * @return false|mixed|string|null
	 */
	public function getDefaultRoleId()
	{
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
				switch ($fState)
				{
					case 'sR400':
						$roleId = Counter\Role::RESPONSIBLE;
						break;

					case 'sR800':
						$roleId = Counter\Role::ACCOMPLICE;
						break;

					case 'sRc00':
						$roleId = Counter\Role::AUDITOR;
						break;

					case 'sRg00':
						$roleId = Counter\Role::ORIGINATOR;
						break;

					default: // all
						$roleId = '';
						break;
				}

				$currentPresetId = $filterOptions->getCurrentFilterId();
				$filterSettings = $filterOptions->getFilterSettings($currentPresetId);

				if (
					is_array($filterSettings['fields'])
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

		foreach (FilterRegistry::getList() as $name)
		{
			$registryId = FilterRegistry::getId($name, $this->getGroupId());
			$options = new Options($registryId, static::getPresets($this));
			$presets = array_merge($presets, $options->getOptions()['filters']);
		}

		return $presets;
	}

	/**
	 * @param Common|null $filterInstance
	 * @return array[]
	 */
	public static function getPresets(Common $filterInstance = null): array
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
			$presets['filter_tasks_scrum'] = [
				'name' => Loc::getMessage('TASKS_PRESET_SCRUM'),
				'default' => true,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS,
						\CTasks::STATE_SUPPOSEDLY_COMPLETED,
						\CTasks::STATE_DEFERRED,
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
					\CTasks::STATE_PENDING,
					\CTasks::STATE_IN_PROGRESS,
					\CTasks::STATE_SUPPOSEDLY_COMPLETED,
					\CTasks::STATE_DEFERRED,
				],
			],
		];
		if ($isScrumProject)
		{
			$presets['filter_tasks_in_progress']['sort'] = 2;
		}

		$presets['filter_tasks_completed'] = [
			'name' => Loc::getMessage('TASKS_PRESET_COMPLETED'),
			'default' => false,
			'fields' => [
				'STATUS' => [\CTasks::STATE_COMPLETED],
			],
		];
		if ($isScrumProject)
		{
			$presets['filter_tasks_completed']['sort'] = 3;
		}

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
					'STATUS' => [\CTasks::STATE_DEFERRED],
				],
			];
			$presets['filter_tasks_expire'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS,
					],
					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
				],
			];
			$presets['filter_tasks_expire_candidate'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED_CAND'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS,
					],
					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
				],
			];
		}

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
			&& !in_array(\CTasks::STATE_COMPLETED, $filter[$statusKey]['REAL_STATUS'])
			&& (int)$this->getUserId() === User::getId()
		)
		{
			$filter[$statusKey] = [
				'::LOGIC' => 'OR',
				'::SUBFILTER-1' => $filter[$statusKey],
				'::SUBFILTER-2' => [
					'WITH_COMMENT_COUNTERS' => 'Y',
					'REAL_STATUS' => \CTasks::STATE_COMPLETED,
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
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_FILTER_CREATED_BY',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
			];
		}

		if (in_array('RESPONSIBLE_ID', $fields))
		{
			$filter['RESPONSIBLE_ID'] = [
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_RESPONSIBLE_ID'),
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_FILTER_RESPONSIBLE_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
				'default' => $isScrumProject,
			];
		}

		if (in_array('STATUS', $fields))
		{
			$statusItems = [
				\CTasks::STATE_PENDING => Loc::getMessage('TASKS_STATUS_2'),
				\CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_STATUS_3'),
				\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_STATUS_4'),
				\CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_STATUS_5'),
				\CTasks::STATE_DEFERRED => Loc::getMessage('TASKS_STATUS_6'),
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
				'type' => 'dest_selector',
				'params' => [
					'context' => 'TASKS_FILTER_GROUP_ID',
					'multiple' => 'Y',
					'contextCode' => 'SG',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'Y',
					'enableDepartments' => 'N',
					'departmentSelectDisable' => 'Y',
					'allowAddSocNetGroup' => 'N',
					'isNumeric' => 'Y',
					'prefix' => 'SG',
				],
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
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_FILTER_ACCOMPLICE',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
			];
		}
		if (in_array('AUDITOR', $fields))
		{
			$filter['AUDITOR'] = [
				'id' => 'AUDITOR',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_AUDITOR'),
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_FILTER_AUDITOR',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
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
			foreach (Counter\Role::getRoles() as $roleCode => $roleName)
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
		];

		if ((int)$this->getGroupId() === 0)
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
			\CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
			\CTaskListState::VIEW_TASK_CATEGORY_NEW,
			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
			\CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL,
			\CTaskListState::VIEW_TASK_CATEGORY_DEFERRED,
			\CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		];
		if ($this->getGroupId() > 0)
		{
			$taskCategories[] = \CTaskListState::VIEW_TASK_CATEGORY_PROJECT_EXPIRED;
			$taskCategories[] = \CTaskListState::VIEW_TASK_CATEGORY_PROJECT_NEW_COMMENTS;
		}
		foreach ($taskCategories as $categoryId)
		{
			$list[$categoryId] = \CTaskListState::getTaskCategoryName($categoryId);
		}

		return $list;
	}

	private function getAllowedTaskScrumCategories(): array
	{
		$list = [];

		$taskCategories = [
			\CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		];
		if ($this->getGroupId() > 0)
		{
			$taskCategories[] = \CTaskListState::VIEW_TASK_CATEGORY_PROJECT_NEW_COMMENTS;
		}
		foreach ($taskCategories as $categoryId)
		{
			$list[$categoryId] = \CTaskListState::getTaskCategoryName($categoryId);
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
	 * @return mixed|null
	 */
	private function getFilterFieldData($field, $default = null)
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

		if ($from = $this->getFilterFieldData("{$rowId}_from"))
		{
			$filter[">{$equalSign}{$rowId}"] = $from;
		}

		if ($to = $this->getFilterFieldData("{$rowId}_to"))
		{
			$filter["<{$equalSign}{$rowId}"] = $to;
		}

		if ($from && $to && $from == $to) // values of type double may be here
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
					case Counter\Type::TYPE_WO_DEADLINE:
						$filter['DEADLINE'] = '';
						break;

					case Counter\Type::TYPE_EXPIRED:
						if ($this->getGroupId() > 0)
						{
							$filter['MEMBER'] = $this->getUserId();
						}
						$filter['<=DEADLINE'] = Counter\Deadline::getExpiredTime();
						$filter['IS_MUTED'] = 'N';
						$filter['REAL_STATUS'] = [\CTasks::STATE_PENDING, \CTasks::STATE_IN_PROGRESS];
						break;

					case Counter\Type::TYPE_EXPIRED_CANDIDATES:
						$filter['>=DEADLINE'] = Counter\Deadline::getExpiredTime();
						$filter['<=DEADLINE'] = Counter\Deadline::getExpiredSoonTime();
						break;

					case Counter\Type::TYPE_WAIT_CTRL:
						$filter['REAL_STATUS'] = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
						$filter['!RESPONSIBLE_ID'] = $this->getUserId();
						$filter['=CREATED_BY'] = $this->getUserId();
						break;

					case Counter\Type::TYPE_NEW:
						$filter['VIEWED'] = 0;
						$filter['VIEWED_BY'] = $this->getUserId();
						break;

					case Counter\Type::TYPE_DEFERRED:
						$filter['REAL_STATUS'] = \CTasks::STATE_DEFERRED;
						break;

					case Counter\Type::TYPE_NEW_COMMENTS:
						if ($this->getGroupId() > 0)
						{
							$filter['MEMBER'] = $this->getUserId();
						}
						$filter['WITH_NEW_COMMENTS'] = 'Y';
						$filter['IS_MUTED'] = 'N';
						break;

					case Counter\Type::TYPE_PROJECT_EXPIRED:
						$filter['PROJECT_EXPIRED'] = 'Y';
						break;

					case Counter\Type::TYPE_PROJECT_NEW_COMMENTS:
						$filter['PROJECT_NEW_COMMENTS'] = 'Y';
						break;

					default:
						break;
				}
				break;

			case 'ROLEID':
				switch ($field)
				{
					case 'view_role_responsible':
						$filter['=RESPONSIBLE_ID'] = $this->getUserId();
						break;

					case 'view_role_originator':
						$filter['=CREATED_BY'] = $this->getUserId();
						$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
						break;

					case 'view_role_accomplice':
						$filter['=ACCOMPLICE'] = $this->getUserId();
						break;

					case 'view_role_auditor':
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
}
