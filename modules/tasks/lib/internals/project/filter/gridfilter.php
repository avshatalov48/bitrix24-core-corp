<?php

namespace Bitrix\Tasks\Internals\Project\Filter;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Tasks\Internals\Project\Filter;
use Bitrix\Tasks\Util\Type\DateTime;

class GridFilter extends Filter
{
	private $filterId;
	private $nameTemplate = '';

	public function __construct(int $userId, $filterId, array $params = [])
	{
		parent::__construct($userId);

		$this->filterId = $filterId;

		if (array_key_exists('NAME_TEMPLATE', $params))
		{
			$this->nameTemplate = $params['NAME_TEMPLATE'];
		}
	}

	public function isUserFilterApplied(): bool
	{
		if ($filterOptions = $this->getFilterOptions())
		{
			$currentPreset = $filterOptions->getCurrentFilterId();
			$isDefaultPreset = ($filterOptions->getDefaultFilterId() === $currentPreset);
			$additionalFields = $filterOptions->getAdditionalPresetFields($currentPreset);
			$isSearchStringEmpty = ($filterOptions->getSearchString() === '');

			return (!$isSearchStringEmpty || !$isDefaultPreset || !empty($additionalFields));
		}

		return false;
	}

	public function resetFilter(): void
	{
		$option = \CUserOptions::GetOption('main.ui.filter', $this->filterId, false, $this->userId);
		if (!$option && ($filterOptions = $this->getFilterOptions()))
		{
			$filterOptions->reset();
		}
	}

	public function getFilterOptions(): ?Options
	{
		static $filterOptions = null;

		if (!$filterOptions)
		{
			$filterOptions = new Options($this->filterId, $this->getPresets());
		}

		return $filterOptions;
	}

	public function getFilterData(): array
	{
		$filterFields = $this->getFilterFields();
		$filterOptions = $this->getFilterOptions();

		return ($filterOptions ? $filterOptions->getFilter($filterFields) : []);
	}

	public function process(Query $query): Query
	{
		$filterFields = $this->getFilterFields();
		$filterData = $this->getFilterData();

		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData['FILTER_APPLIED'] !== true)
		{
			return $query;
		}

		if (array_key_exists('FIND', $filterData) && trim($filterData['FIND']) !== '')
		{
			$query = $this->processFilterSearch($query, $filterData['FIND']);
		}

		foreach ($filterFields as $filterRow)
		{
			$id = $filterRow['id'];
			$type = $filterRow['type'];

			switch ($type)
			{
				case 'number':
					$query = $this->handleNumberFilterRow($id, $filterData, $query);
					break;

				case 'string':
					$query = $this->handleStringFilterRow($id, $filterData, $query);
					break;

				case 'date':
					$query = $this->handleDateFilterRow($id, $filterData, $query);
					break;

				case 'list':
					$query = $this->handleListFilterRow($id, $filterData, $query);
					break;

				case 'dest_selector':
					$query = $this->handleEntitySelectorFilterRow($id, $filterData, $query);
					break;

				default:
					break;
			}
		}

		return $query;
	}

	private function handleNumberFilterRow($id, $filterData, Query $query): Query
	{
		$from = "{$id}_from";
		$to = "{$id}_to";
		$less = "<={$id}";
		$more = ">={$id}";

		$filter = [];

		if (array_key_exists($from, $filterData) && !empty($filterData[$from]))
		{
			$filter[$more] = Query::filter()->where($id, '>=', $filterData[$from]);
		}
		if (array_key_exists($to, $filterData) && !empty($filterData[$to]))
		{
			$filter[$less] = Query::filter()->where($id, '<=', $filterData[$to]);
		}

		if (
			array_key_exists($more, $filter)
			&& array_key_exists($less, $filter)
			&& $filter[$more] === $filter[$less]
		)
		{
			$filter[$id] = $filter[$more];
			unset($filter[$more], $filter[$less]);
		}

		foreach ($filter as $condition)
		{
			$query->where($condition);
		}

		return $query;
	}

	private function handleStringFilterRow($id, $filterData, Query $query): Query
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return $query;
		}

		if ($id === 'TAGS')
		{
			$query = $this->processFilterTags($query, $filterData[$id]);
		}
		else
		{
			$query->whereLike($id, $filterData[$id]);
		}

		return $query;
	}

	private function handleDateFilterRow($id, $filterData, Query $query): Query
	{
		$from = "{$id}_from";
		$to = "{$id}_to";

		if (array_key_exists($from, $filterData) && !empty($filterData[$from]))
		{
			$date = MakeTimeStamp($filterData[$from]);
			$date = DateTime::createFromTimestamp($date);
			$query->where("{$id}_START", '>=', $date);
		}
		if (array_key_exists($to, $filterData) && !empty($filterData[$to]))
		{
			$date = MakeTimeStamp($filterData[$to]);
			$date = DateTime::createFromTimestamp($date);
			$query->where("{$id}_FINISH", '<=', $date);
		}

		return $query;
	}

	private function handleListFilterRow($id, $filterData, Query $query): Query
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return $query;
		}

		if ($id === 'CLOSED')
		{
			$query = $this->processFilterIsClosed($query, $filterData[$id]);
		}
		else if ($id === 'IS_PROJECT')
		{
			$query = $this->processFilterIsProject($query, $filterData[$id]);
		}
		else if ($id === 'TYPE')
		{
			$query = $this->processFilterType($query, $filterData[$id]);
		}
		elseif ($id === 'COUNTERS')
		{
			$query = $this->processFilterCounters($query, $filterData[$id]);
		}

		return $query;
	}

	private function handleEntitySelectorFilterRow($id, $filterData, Query $query): Query
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return $query;
		}

		if ($id === 'OWNER_ID')
		{
			$query = $this->processFilterOwner($query, $filterData[$id]);
		}
		elseif ($id === 'MEMBER_ID')
		{
			$query = $this->processFilterMember($query, $filterData[$id]);
		}

		return $query;
	}

	public function getFilterFields(): array
	{
		if ($this->isScrum)
		{
			$counterItems = [
				'NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_NEW_COMMENTS'),
				'PROJECT_NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_PROJECT_NEW_COMMENTS'),
			];
		}
		else
		{
			$counterItems = [
				'EXPIRED' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_EXPIRED'),
				'NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_NEW_COMMENTS'),
				'PROJECT_EXPIRED' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_PROJECT_EXPIRED'),
				'PROJECT_NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS_PROJECT_NEW_COMMENTS'),
			];
		}

		$fields = [
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_NAME'),
				'type' => 'string',
				'default' => true,
			],
			'OWNER_ID' => [
				'id' => 'OWNER_ID',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_DIRECTOR'),
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_PROJECTS_FILTER_OWNER_ID',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
				'default' => true,
			],
			'MEMBER' => [
				'id' => 'MEMBER_ID',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_MEMBER'),
				'type' => 'dest_selector',
				'params' => [
					'apiVersion' => '3',
					'context' => 'TASKS_PROJECTS_FILTER_MEMBER_ID',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'Y',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				],
				'default' => true,
			],
			'CLOSED' => [
				'id' => 'CLOSED',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_CLOSED'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_CLOSED_Y'),
					'N' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_CLOSED_N'),
				],
			],
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_ID'),
				'type' => 'number',
				'default' => false,
			],
			'TAGS' => [
				'id' => 'TAGS',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_TAG'),
				'type' => 'string',
				'default' => false,
			],
			'COUNTERS' => [
				'id' => 'COUNTERS',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_COUNTERS'),
				'type' => 'list',
				'items' => $counterItems,
			],
		];

		if (!$this->isScrum)
		{
			$fields['IS_PROJECT'] = [
				'id' => 'IS_PROJECT',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_IS_PROJECT'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_IS_PROJECT_Y'),
					'N' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_IS_PROJECT_N'),
				],
			];
			$fields['TYPE'] = [
				'id' => 'TYPE',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_TYPE'),
				'type' => 'list',
				'items' => $this->getProjectTypes(),
			];
			$fields['PROJECT_DATE'] = [
				'id' => 'PROJECT_DATE',
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_FIELD_PROJECT_DATE'),
				'type' => 'date',
			];
		}

		return $fields;
	}

	public function getPresets(): array
	{
		return [
			'my' => [
				'name' => $this->isScrum
					? Loc::getMessage('TASKS_PROJECT_GRID_FILTER_SCRUM_PRESET_MY')
					: Loc::getMessage('TASKS_PROJECT_GRID_FILTER_PRESET_MY')
				,
				'fields' => [
					'CLOSED' => 'N',
					'MEMBER_ID' => $this->userId,
					'MEMBER_ID_label' => $this->getCurrentUserName(),
				],
				'default' => true,
			],
			'active_project' => [
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_PRESET_ACTIVE_PROJECT'),
				'fields' => [
					'CLOSED' => 'N',
				],
				'default' => false,
			],
			'inactive_project' => [
				'name' => Loc::getMessage('TASKS_PROJECT_GRID_FILTER_PRESET_INACTIVE_PROJECT'),
				'fields' => [
					'CLOSED' => 'Y',
				],
				'default' => false,
			],
		];
	}

	private function getCurrentUserName(): string
	{
		$result = \CUser::GetList(
			'',
			'',
			['ID_EQUAL_EXACT' => $this->userId],
			['FIELDS' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN']]
		);
		if ($user = $result->Fetch())
		{
			return \CUser::FormatName($this->nameTemplate, $user, true, false);
		}

		return '';
	}
}
