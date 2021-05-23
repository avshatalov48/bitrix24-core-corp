<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;
use Bitrix\Tasks\Util\User;

class Filter extends Common
{
	protected static $instance = null;

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
			if ($fState && !is_array($fState) && mb_substr($fState, 0, 2) == 'sR')
			{
				$roleCode = $request->get('F_STATE');

				switch ($roleCode)
				{
					case 'sR400': // i do
						$roleId = Counter\Role::RESPONSIBLE;
						break;
					case 'sR800': // acc
						$roleId = Counter\Role::ACCOMPLICE;
						break;
					case 'sRc00': // au
						$roleId = Counter\Role::AUDITOR;
						break;
					case 'sRg00': // orig
						$roleId = Counter\Role::ORIGINATOR;
						break;
					default: // all
						$roleId = '';
						break;
				}

				$currentPresetId = $filterOptions->getCurrentFilterId();
				$filterSettings = $filterOptions->getFilterSettings($currentPresetId);

				if (is_array($filterSettings['fields']) && (!array_key_exists('ROLEID', $filterSettings['fields']) || !$filterSettings['fields']['ROLEID']))
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
				$roleId = $filter['ROLEID'];
			}
		}

		return $roleId;
	}

	/**
	 * @return \Bitrix\Main\UI\Filter\Options
	 */
	public function getOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = new \Bitrix\Main\UI\Filter\Options($this->getId(), static::getPresets($this));
		}

		return $instance;
	}

	/**
	 * @param Common|null $filterInstance
	 * @return array[]
	 */
	public static function getPresets(Common $filterInstance = null)
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
				'name' => 'SCRUM',
				'default' => true,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS,
						\CTasks::STATE_SUPPOSEDLY_COMPLETED,
						\CTasks::STATE_DEFERRED,
						EntityTable::STATE_COMPLETED_IN_ACTIVE_SPRINT
					],
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
				]
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
				'STATUS' => [
					\CTasks::STATE_COMPLETED
				]
			]
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
				'fields' => [
					'RESPONSIBLE_ID' => $userId
				],
				'sort' => 4
			];
		}

		if (!$isScrumProject)
		{
			$presets['filter_tasks_deferred'] = [
				'name' => Loc::getMessage('TASKS_PRESET_DEFERRED'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_DEFERRED
					]
				]
			];
			$presets['filter_tasks_expire'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS
					],
					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
				]
			];
			$presets['filter_tasks_expire_candidate'] = [
				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED_CAND'),
				'default' => false,
				'fields' => [
					'STATUS' => [
						\CTasks::STATE_PENDING,
						\CTasks::STATE_IN_PROGRESS
					],
					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
				]
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
		$filter = $this->postProcess($filter);

		return $filter;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private function postProcess(array $filter): array
	{
		$filter['ZOMBIE'] = 'N';
		$filter['CHECK_PERMISSIONS'] = 'Y';
		$filter['ONLY_ROOT_TASKS'] = 'Y';

		if ($this->isScrumProject())
		{
			$epicKey = '::SUBFILTER-EPIC';
			if (isset($filter[$epicKey]))
			{
				$filter['EPIC'] = $filter[$epicKey]['EPIC'];
				unset($filter[$epicKey]);
			}
		}

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
			if (!array_key_exists('uf', $filterRow) || $filterRow['uf'] != true)
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
					$data = $this->getDestSelectorFilterFieldData($filterRow);
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

	private function processMainFilter()
	{
		$filter = [];
		$filters = $this->getFilters();

		$this->getDefaultRoleId();
		$groupId = $this->getGroupId();
		$prefix = '::SUBFILTER-';

		if ($groupId > 0)
		{
			$filter['GROUP_ID'] = $groupId;
		}

		if ($this->isFilterEmpty() && $groupId == 0)
		{
			$filter['::SUBFILTER-ROLEID']['MEMBER'] = $this->getUserId(); //TODO
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
					$field = $this->getFilterFieldData($filterRow['id']);
					if ($field)
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
					$rawFilter = $this->getDestSelectorFilterFieldData($filterRow);
					break;

				case 'string':
					$field = $this->getFilterFieldData($filterRow['id']);
					if ($field)
					{
						$rawFilter['%' . $filterRow['id']] = $field;
					}
					break;

				case 'fulltext':
					$field = $this->getFilterFieldData($filterRow['id']);
					if ($field)
					{
						$rawFilter[$filterRow['id']] = $field;
					}
					break;
			}

			if ($rawFilter)
			{
				$filter[$prefix . $fieldId] = $rawFilter;
			}
		}

		$filter = $this->postProcessMainFilter($filter);

		return $filter;
	}

	/**
	 * @param array $filter
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\SystemException
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
			isset($filter[$statusKey])
			&& (int) $this->getUserId() === (int) User::getId()
		)
		{
			$filter[$statusKey] = [
				'::LOGIC' => 'OR',
				'::SUBFILTER-1' => $filter[$statusKey],
				'::SUBFILTER-2' => [
					'WITH_COMMENT_COUNTERS' => 'Y',
					'REAL_STATUS' => \CTasks::STATE_COMPLETED
				],
			];
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		static $filters = array();

		if (empty($filters))
		{
			$filters = $this->getFilterRaw();
		}

		return $filters;
	}

	/**
	 * @return array
	 */
	private function getFilterRaw()
	{
		$fields = $this->getAvailableFields();
		$filter = array();

		$isScrumProject = $this->isScrumProject();

		if (in_array('CREATED_BY', $fields))
		{
			$filter['CREATED_BY'] = array(
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_CREATED_BY'),
				'type' => 'dest_selector',
				'params' => array (
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
				)
			);
		}

		if (in_array('RESPONSIBLE_ID', $fields))
		{
			$filter['RESPONSIBLE_ID'] = array(
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_RESPONSIBLE_ID'),
				'type' => 'dest_selector',
				'params' => array (
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
				),
				'default' => $isScrumProject
			);
		}

		if (in_array('STATUS', $fields))
		{
			$statusItems = [
				\CTasks::STATE_PENDING => Loc::getMessage('TASKS_STATUS_2'),
				\CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_STATUS_3'),
				\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_STATUS_4'),
				\CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_STATUS_5'),
				\CTasks::STATE_DEFERRED => Loc::getMessage('TASKS_STATUS_6'),
			];
			if ($this->isTaskScrumEnabled())
			{
				$statusItems[EntityTable::STATE_COMPLETED_IN_ACTIVE_SPRINT] = Loc::getMessage('TASKS_STATUS_8');
			}
			$filter['STATUS'] = array(
				'id' => 'STATUS',
				'name' => Loc::getMessage('TASKS_FILTER_STATUS'),
				'type' => 'list',
				'params' => array(
					'multiple' => 'Y'
				),
				'items' => $statusItems,
				'default' => $isScrumProject
			);
		}

		if (in_array('DEADLINE', $fields))
		{
			$filter['DEADLINE'] = array(
				'id' => 'DEADLINE',
				'name' => Loc::getMessage('TASKS_FILTER_DEADLINE'),
				'type' => 'date'
			);
		}

		if (in_array('GROUP_ID', $fields))
		{
			$filter['GROUP_ID'] = array(
				'id' => 'GROUP_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_GROUP'),
				'type' => 'dest_selector',
				'params' => array (
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
				)
			);
		}

		if (in_array('PROBLEM', $fields))
		{
			$filter['PROBLEM'] = array(
				'id' => 'PROBLEM',
				'name' => Loc::getMessage('TASKS_FILTER_PROBLEM'),
				'type' => 'list',
				'items' => $this->getAllowedTaskCategories()
			);
		}

		if (in_array('PARAMS', $fields))
		{
			$filter['PARAMS'] = array(
				'id' => 'PARAMS',
				'name' => Loc::getMessage('TASKS_FILTER_PARAMS'),
				'type' => 'list',
				'params' => array(
					'multiple' => 'Y'
				),
				'items' => array(
					'MARKED' => Loc::getMessage('TASKS_FILTER_PARAMS_MARKED'),
					'IN_REPORT' => Loc::getMessage('TASKS_FILTER_PARAMS_IN_REPORT'),
					'OVERDUED' => Loc::getMessage('TASKS_FILTER_PARAMS_OVERDUED'),
					//					'SUBORDINATE'=>Loc::getMessage('TASKS_FILTER_PARAMS_SUBORDINATE'),
					'FAVORITE' => Loc::getMessage('TASKS_FILTER_PARAMS_FAVORITE'),
					'ANY_TASK' => Loc::getMessage('TASKS_FILTER_PARAMS_ANY_TASK')
				)
			);
		}

		if (in_array('ID', $fields))
		{
			$filter['ID'] = array(
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_FILTER_ID'),
				'type' => 'number'
			);
		}
		if (in_array('TITLE', $fields))
		{
			$filter['TITLE'] = array(
				'id' => 'TITLE',
				'name' => Loc::getMessage('TASKS_FILTER_TITLE'),
				'type' => 'string'
			);
		}
		if (in_array('PRIORITY', $fields))
		{
			$filter['PRIORITY'] = array(
				'id' => 'PRIORITY',
				'name' => Loc::getMessage('TASKS_PRIORITY'),
				'type' => 'list',
				'items' => array(
					1 => Loc::getMessage('TASKS_PRIORITY_1'),
					2 => Loc::getMessage('TASKS_PRIORITY_2'),
				)
			);
		}
		if (in_array('MARK', $fields))
		{
			$filter['MARK'] = array(
				'id' => 'MARK',
				'name' => Loc::getMessage('TASKS_FILTER_MARK'),
				'type' => 'list',
				'items' => array(
					'P' => Loc::getMessage('TASKS_MARK_P'),
					'N' => Loc::getMessage('TASKS_MARK_N')
				)
			);
		}
		if (in_array('ALLOW_TIME_TRACKING', $fields))
		{
			$filter['ALLOW_TIME_TRACKING'] = array(
				'id' => 'ALLOW_TIME_TRACKING',
				'name' => Loc::getMessage('TASKS_FILTER_ALLOW_TIME_TRACKING'),
				'type' => 'list',
				'items' => array(
					'Y' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_Y'),
					'N' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_N'),
				)
			);
		}
		if (in_array('CREATED_DATE', $fields))
		{
			$filter['CREATED_DATE'] = array(
				'id' => 'CREATED_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_CREATED_DATE'),
				'type' => 'date'
			);
		}
		if (in_array('CLOSED_DATE', $fields))
		{
			$filter['CLOSED_DATE'] = array(
				'id' => 'CLOSED_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_CLOSED_DATE'),
				'type' => 'date'
			);
		}
		if (in_array('DATE_START', $fields))
		{
			$filter['DATE_START'] = array(
				'id' => 'DATE_START',
				'name' => Loc::getMessage('TASKS_FILTER_DATE_START'),
				'type' => 'date'
			);
		}
		if (in_array('START_DATE_PLAN', $fields))
		{
			$filter['START_DATE_PLAN'] = array(
				'id' => 'START_DATE_PLAN',
				'name' => Loc::getMessage('TASKS_FILTER_START_DATE_PLAN'),
				'type' => 'date'
			);
		}
		if (in_array('END_DATE_PLAN', $fields))
		{
			$filter['END_DATE_PLAN'] = array(
				'id' => 'END_DATE_PLAN',
				'name' => Loc::getMessage('TASKS_FILTER_END_DATE_PLAN'),
				'type' => 'date'
			);
		}

		if (in_array('ACTIVE', $fields))
		{
			$filter['ACTIVE'] = array(
				'id' => 'ACTIVE',
				'name' => Loc::getMessage('TASKS_FILTER_ACTIVE'),
				'type' => 'date'
			);
		}

		if (in_array('ACCOMPLICE', $fields))
		{
			$filter['ACCOMPLICE'] = array(
				'id' => 'ACCOMPLICE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_ACCOMPLICES'),
				'type' => 'dest_selector',
				'params' => array (
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
				)
			);
		}
		if (in_array('AUDITOR', $fields))
		{
			$filter['AUDITOR'] = array(
				'id' => 'AUDITOR',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_AUDITOR'),
				'type' => 'dest_selector',
				'params' => array (
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
				)
			);
		}

		if (in_array('TAG', $fields))
		{
			$filter['TAG'] = array(
				'id' => 'TAG',
				'name' => Loc::getMessage('TASKS_FILTER_TAG'),
				'type' => 'string',
				'default' => $isScrumProject
			);
		}

		if ($isScrumProject)
		{
			$filter['EPIC'] = array(
				'id' => 'EPIC',
				'name' => Loc::getMessage('TASKS_FILTER_EPIC'),
				'type' => 'list',
				'items' => $this->getEpics(),
				'default' => true
			);
		}

		if (in_array('ROLEID', $fields))
		{
			$items = array();
			foreach (Counter\Role::getRoles() as $roleCode => $roleName)
			{
				$items[$roleCode] = $roleName['TITLE'];
			}
			$filter['ROLEID'] = array(
				'id' => 'ROLEID',
				'name' => Loc::getMessage('TASKS_FILTER_ROLEID'),
				'type' => 'list',
				'default' => !$isScrumProject,
				'items' => $items
			);
		}

		if (in_array('COMMENT', $fields))
		{
			$filter['COMMENT_SEARCH_INDEX'] = array(
				'id' => 'COMMENT_SEARCH_INDEX',
				'name' => Loc::getMessage('TASKS_FILTER_COMMENT'),
				'type' => 'fulltext'
			);
		}

		if (in_array('CREATED_DATE', $fields))
		{
			$filter['ACTIVITY_DATE'] = array(
				'id' => 'ACTIVITY_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_ACTIVITY_DATE'),
				'type' => 'date'
			);
		}

		$uf = $this->getUF();
		if (!empty($uf))
		{
			foreach ($uf as $item)
			{
				$type = $item['USER_TYPE_ID'];
				$available = ['datetime', 'string', 'double', 'boolean', 'crm'];
				if (!in_array($type, $available))
				{
					$type = 'string';
				}

				if ($type == 'datetime')
				{
					$type = 'date';
				}

				if ($type == 'double')
				{
					$type = 'number';
				}

				if ($type == 'boolean')
				{
					$filter[$item['FIELD_NAME']] = array(
						'id' => $item['FIELD_NAME'],
						'name' => $item['EDIT_FORM_LABEL'],
						'type' => 'list',
						'items' => [
							1 => GetMessage('TASKS_FILTER_NO'),
							2 => GetMessage('TASKS_FILTER_YES')
						],
						'uf' => true
					);
				}
				else if ($type == 'crm')
				{
					continue;
					$supportedEntityTypeNames = array(
						\CCrmOwnerType::LeadName,
						\CCrmOwnerType::DealName,
						\CCrmOwnerType::ContactName,
						\CCrmOwnerType::CompanyName
					);
					$entityTypeNames = [];
					foreach ($supportedEntityTypeNames as $entityTypeName)
					{
						$entityTypeNames[] = $entityTypeName;
					}

					$filter[$item['FIELD_NAME']] = array(
						'id' => $item['FIELD_NAME'],
						'name' => $item['EDIT_FORM_LABEL'],
						'type' => 'custom_entity',
						'params' => array('multiple' => 'Y'),
						'selector' => array(
							'TYPE' => 'companies',
							'DATA' => array(
								'ID' => mb_strtolower($item['FIELD_NAME']),
								'FIELD_ID' => $item['FIELD_NAME'],
								'ENTITY_TYPE_NAMES' => $entityTypeNames,
								'IS_MULTIPLE' => 'Y'
							)
						)
					);
				}
				else
				{
					$filter[$item['FIELD_NAME']] = array(
						'id' => $item['FIELD_NAME'],
						'name' => $item['EDIT_FORM_LABEL'],
						'type' => $type,
						'uf' => true
					);
				}
			}
		}

		return $filter;
	}

	/**
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function isTaskScrumEnabled(): bool
	{
		return (Option::get('tasks', 'tasks_scrum_enabled', 'N') === 'Y');
	}

	/**
	 * Get available fields in filter.
	 * @return array
	 */
	public function getAvailableFields()
	{
		$fields = array(
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
			'ACTIVITY_DATE'
		);

		if ($this->getGroupId() == 0)
		{
			$fields[] = 'GROUP_ID';
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	private function getAllowedTaskCategories()
	{
		$list = array();

		$taskCategories = array(
			\CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
			\CTaskListState::VIEW_TASK_CATEGORY_NEW,
			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
			\CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL,
			\CTaskListState::VIEW_TASK_CATEGORY_DEFERRED,
			\CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		);

		foreach ($taskCategories as $categoryId)
		{
			$list[$categoryId] = \CTaskListState::getTaskCategoryName($categoryId);
		}

		return $list;
	}

	public function isSearchFieldApplied(): bool
	{
		return (!$this->isFilterEmpty() && $this->getFilterFieldData('FIND', ''));
	}

	private function isFilterEmpty()
	{
		return !$this->getFilterFieldData('FILTER_APPLIED', false);
	}

	private function getFilterFieldData($field, $default = null)
	{
		$filterData = $this->getFilterData();

		return array_key_exists($field, $filterData) ? $filterData[$field] : $default;
	}

	/**
	 * @return array
	 */
	private function getFilterData()
	{
		$filters = $this->getFilters();
		$filterOptions = $this->getOptions();

		return $filterOptions->getFilter($filters);
	}

	private function getDateFilterFieldData($row)
	{
		$arrFilter = array();

		if ($row['id'] == 'ACTIVE' && !empty($this->getFilterFieldData($row['id'].'_from')))
		{
			$arrFilter['ACTIVE']['START'] = $this->getFilterFieldData($row['id'].'_from');
			$arrFilter['ACTIVE']['END'] = $this->getFilterFieldData($row['id'].'_to');

			return $arrFilter;
		}

		if ($this->getFilterFieldData($row['id'].'_from'))
		{
			$arrFilter['>='.$row['id']] = $this->getFilterFieldData($row['id'].'_from');
		}

		if ($this->getFilterFieldData($row['id'].'_to'))
		{
			$arrFilter['<='.$row['id']] = $this->getFilterFieldData($row['id'].'_to');
		}

		return $arrFilter;
	}

	private function getNumberFilterFieldData($row)
	{
		$arrFilter = [];
		$rowId = $row['id'];
		$equalSign = ($this->getFilterFieldData($rowId . '_numsel') == 'range'? '=' : '');

		if ($this->getFilterFieldData($rowId . '_from'))
		{
			$arrFilter['>' . $equalSign . $rowId] = $this->getFilterFieldData($rowId . '_from');
		}
		if ($this->getFilterFieldData($rowId . '_to'))
		{
			$arrFilter['<' . $equalSign . $rowId] = $this->getFilterFieldData($rowId . '_to');
		}

		if (array_key_exists('>' . $equalSign . $rowId, $arrFilter) &&
			array_key_exists('<' . $equalSign . $rowId, $arrFilter) &&
			$arrFilter['>' . $equalSign . $rowId] == $arrFilter['<' . $equalSign . $rowId])
		{
			$arrFilter[$rowId] = $arrFilter['>' . $equalSign . $rowId];
			unset($arrFilter['>' . $equalSign . $rowId], $arrFilter['<' . $equalSign . $rowId]);
		}

		return $arrFilter;
	}

	private function getListFilterFieldData($row)
	{
		$arrFilter = array();
		$field = $this->getFilterFieldData($row['id'], array());

		switch ($row['id'])
		{
			case 'STATUS':
				$arrFilter['REAL_STATUS'] = $field; //TODO!!!
				break;

			case 'PROBLEM':
				switch ($field)
				{
					case Counter\Type::TYPE_WO_DEADLINE:
						switch ($this->getFilterFieldData('ROLEID'))
						{
							case Counter\Role::RESPONSIBLE:
								$arrFilter['!CREATED_BY'] = $this->getUserId();
								break;

							case Counter\Role::ORIGINATOR:
								$arrFilter['!RESPONSIBLE_ID'] = $this->getUserId();
								break;

							default:
								if ($this->getGroupId() > 0)
								{
									$arrFilter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
								}
								else
								{
									$userId = $this->getUserId();

									$arrFilter['::SUBFILTER-OR'] = [
										'::LOGIC' => 'OR',
										'::SUBFILTER-R' => [
											'!CREATED_BY' => $userId,
											'RESPONSIBLE_ID' => $userId,
										],
										'::SUBFILTER-O' => [
											'CREATED_BY' => $userId,
											'!RESPONSIBLE_ID' => $userId,
										],
									];
								}
								break;
						}
						$arrFilter['DEADLINE'] = '';
						break;

					case Counter\Type::TYPE_EXPIRED:
						if ($this->getGroupId() > 0)
						{
							$arrFilter['MEMBER'] = $this->getUserId();
						}
						$arrFilter['<=DEADLINE'] = Counter\Deadline::getExpiredTime();
						$arrFilter['IS_MUTED'] = 'N';
						$arrFilter['REAL_STATUS'] = [\CTasks::STATE_PENDING, \CTasks::STATE_IN_PROGRESS];
						break;

					case Counter\Type::TYPE_EXPIRED_CANDIDATES:
						$arrFilter['>=DEADLINE'] = Counter\Deadline::getExpiredTime();
						$arrFilter['<=DEADLINE'] = Counter\Deadline::getExpiredSoonTime();
						break;

					case Counter\Type::TYPE_WAIT_CTRL:
						$arrFilter['REAL_STATUS'] = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
						$arrFilter['!RESPONSIBLE_ID'] = $this->getUserId();
						$arrFilter['=CREATED_BY'] = $this->getUserId();
						break;

					case Counter\Type::TYPE_NEW:
						$arrFilter['VIEWED'] = 0;
						$arrFilter['VIEWED_BY'] = $this->getUserId();
						break;

					case Counter\Type::TYPE_DEFERRED:
						$arrFilter['REAL_STATUS'] = \CTasks::STATE_DEFERRED;
						break;

					case Counter\Type::TYPE_NEW_COMMENTS:
						if ($this->getGroupId() > 0)
						{
							$arrFilter['MEMBER'] = $this->getUserId();
						}
						$arrFilter['WITH_NEW_COMMENTS'] = 'Y';
						$arrFilter['IS_MUTED'] = 'N';
						break;

					default:
						break;
				}
				break;

			case 'ROLEID':
				switch ($field)
				{
					case 'view_role_responsible':
						$arrFilter['=RESPONSIBLE_ID'] = $this->getUserId();
						break;

					case 'view_role_originator':
						$arrFilter['=CREATED_BY'] = $this->getUserId();
						$arrFilter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
						break;

					case 'view_role_accomplice':
						$arrFilter['=ACCOMPLICE'] = $this->getUserId();
						break;

					case 'view_role_auditor':
						$arrFilter['=AUDITOR'] = $this->getUserId();
						break;

					default:
						if (!$this->getGroupId())
						{
							$arrFilter['MEMBER'] = $this->getUserId();
						}
						break;
				}
				break;

			case 'PARAMS':
				foreach ($field as $item)
				{
					switch ($item)
					{
						case 'FAVORITE':
							$arrFilter["FAVORITE"] = 'Y';
							break;

						case 'MARKED':
							$arrFilter["!MARK"] = false;
							break;

						case 'OVERDUED':
							$arrFilter["OVERDUED"] = "Y";
							break;

						case 'IN_REPORT':
							$arrFilter["ADD_IN_REPORT"] = "Y";
							break;

						case 'SUBORDINATE':
							// Don't set SUBORDINATE_TASKS for admin, it will cause all tasks to be showed
							if (!\Bitrix\Tasks\Util\User::isSuper())
							{
								$arrFilter["SUBORDINATE_TASKS"] = "Y";
							}
							break;

						case 'ANY_TASK':
							$arrFilter['::REMOVE-MEMBER'] = true; // hack
							break;
					}
				}
				break;

			default:
				if ($field)
				{
					$arrFilter[$row['id']] = $field;
				}
				break;
		}

		return $arrFilter;
	}

	private function getDestSelectorFilterFieldData($row)
	{
		$arrFilter = [];
		$rowId = $row['id'];
		$value = $this->getFilterFieldData($rowId);

		if (!empty($value))
		{
			$arrFilter[$rowId] = $value;
		}

		return $arrFilter;
	}

	public function getDefaultPresetKey()
	{
		return $this->getOptions()->getDefaultFilterId();
	}

	/**
	 * @return \Bitrix\Tasks\Util\UserField|array|null|string
	 */
	private function getUF()
	{
		$uf = \Bitrix\Tasks\Item\Task::getUserFieldControllerClass();

		$scheme = $uf::getScheme();
		unset($scheme['UF_TASK_WEBDAV_FILES'], $scheme['UF_MAIL_MESSAGE']);

		return $scheme;
	}

	private function getEpics(): array
	{
		$backlogService = new BacklogService();
		$itemService = new ItemService();

		$backlog = $backlogService->getBacklogByGroupId($this->getGroupId());
		if ($backlogService->getErrors() || $backlog->isEmpty())
		{
			return [];
		}

		$epics = [];
		foreach ($itemService->getAllEpicTags($backlog->getId()) as $epic)
		{
			$epics[$epic['id']] = $epic['name'];
		}

		return $epics;
	}
}