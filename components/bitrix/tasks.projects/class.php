<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Grid;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\SocialNetwork\WorkgroupTagTable;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionController;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\TourGuide;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

/**
 * Class TasksProjectsComponent
 */
class TasksProjectsComponent extends CBitrixComponent implements Controllerable
{
	/** @var ConditionTree */
	private static $projectVisibilityCondition;

	/** @var Collection */
	private $errors;

	private function checkModules(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errors->add(
				'SOCIALNETWORK_MODULE_NOT_INSTALLED',
				'Socialnetwork module is not installed'
			);
		}

		return $this->errors->checkNoFatals();
	}

	private function checkPermissions(): bool
	{
		$currentUserId = $this->arResult['USER_ID'] = User::getId();
		$targetUserId = (int)$this->arParams['USER_ID'];

		if (!$currentUserId)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				'Current user is not defined'
			);
		}

		if ($currentUserId !== $targetUserId)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				'Access denied'
			);
		}

		return $this->errors->checkNoFatals();
	}

	private function checkParameters(): void
	{
		$this->arParams['PATH_TO_USER_TASKS_TASK'] = CComponentEngine::makePathFromTemplate(
			'/company/personal/user/#user_id#/tasks/task/#action#/0/',
			[
				'user_id' => $this->arParams['USER_ID'],
				'action' => 'edit',
			]
		);
		$pathToGroupCreate = CComponentEngine::makePathFromTemplate(
			($this->arParams['PATH_TO_GROUP_CREATE'] ?? '/company/personal/user/#user_id#/groups/create/'),
			['user_id' => $this->arParams['USER_ID']]
		);
		$this->arParams['PATH_TO_GROUP_CREATE'] =
			(new Uri($pathToGroupCreate))
				->addParams([
					'firstRow' => 'project',
					'refresh' => 'N',
				])
				->getUri()
		;
		$this->arResult['GRID_ID'] = $this->arParams['GRID_ID'];
	}

	private function getOrder(): array
	{
		$order = ['IS_PINNED' => 'desc'];

		$gridOptions = new Grid\Options($this->arParams['GRID_ID']);
		$sorting = $gridOptions->GetSorting($this->getDefaultSorting());

		return array_merge($order, $sorting['sort']);
	}

	private function getGridSort(): array
	{
		$order = $this->getOrder();
		unset($order['IS_PINNED']);

		reset($order);
		$field = key($order);
		$direction = current($order);
		$direction = ($direction ? explode(',', $direction)[0] : 'asc');

		return [$field => $direction];
	}

	private function getDefaultSorting(): array
	{
		return [
			'sort' => ['ACTIVITY_DATE' => 'desc'],
			'vars' => [
				'by' => 'by',
				'order' => 'order',
			],
		];
	}

	private function doPreAction(): void
	{
		if (!$this->request->isAjaxRequest())
		{
			$option = CUserOptions::GetOption(
				'main.ui.filter',
				$this->arResult['GRID_ID'],
				false,
				$this->arResult['USER_ID']
			);
			if (!$option && ($filterOptions = $this->getFilterOptions()))
			{
				$filterOptions->reset();
			}
		}
	}

	private function getData(): void
	{
		$this->arResult['FILTERS'] = $this->getFilterFields();
		$this->arResult['PRESETS'] = $this->getPresetFields();

		$gridSort = $this->getGridSort();
		$this->arParams['GRID_SORT'] = $gridSort;
		$this->arResult['SORT'] = $gridSort;

		$filterFields = $this->getFilterFields();
		$filterOptions = $this->getFilterOptions();
		$this->arParams['FILTER_DATA'] = ($filterOptions ? $filterOptions->getFilter($filterFields) : []);

		$this->arResult['GROUPS'] = $this->getGroups();
		$this->arResult['GRID'] = new Bitrix\Tasks\Grid\Project\Grid($this->arResult['GROUPS'], $this->arParams);
		$this->arResult['HEADERS'] = $this->arResult['GRID']->prepareHeaders();
		$this->arResult['STUB'] = $this->getStub();

		$preparedRows = $this->arResult['GRID']->prepareRows();

		$this->arResult['ROWS'] = [];
		foreach ($this->arResult['GROUPS'] as $id => $row)
		{
			$this->arResult['ROWS'][] = [
				'id' => $row['ID'],
				'columns' => $preparedRows[$id]['content'],
				'actions' => $preparedRows[$id]['actions'],
				'cellActions' => $preparedRows[$id]['cellActions'],
				'counters' => $preparedRows[$id]['counters'],
			];
		}
	}

	private function getStub(): array
	{
		if ($this->isUserFilterApplied())
		{
			return [
				'title' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_NO_DATA_TITLE'),
				'description' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_NO_DATA_DESCRIPTION'),
			];
		}

		return [
			'title' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_TITLE'),
			'description' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_DESCRIPTION'),
		];
	}

	private function isUserFilterApplied(): bool
	{
		if ($filterOptions = $this->getFilterOptions())
		{
			$currentPreset = $filterOptions->getCurrentFilterId();
			$isDefaultPreset = $filterOptions->getDefaultFilterId() === $currentPreset;
			$additionalFields = $filterOptions->getAdditionalPresetFields($currentPreset);
			$isSearchStringEmpty = $filterOptions->getSearchString() === '';

			return !$isSearchStringEmpty || !$isDefaultPreset || !empty($additionalFields);
		}

		return false;
	}

	private function getCurrentPage(): int
	{
		if (isset($this->arParams['PAGE_NUMBER']) || isset($_REQUEST['page']))
		{
			$pageNum = (int)($this->arParams['PAGE_NUMBER'] ?? $_REQUEST['page']);

			return ($pageNum < 0 ? 1 : $pageNum);
		}

		return 1;
	}

	private function getGroups(): array
	{
		$nav = new PageNavigation('page');
		$nav
			->allowAllRecords(true)
			->setPageSize(10)
			->setCurrentPage($this->getCurrentPage())
		;

		$query = $this->getPrimaryProjectsQuery();
		$query
			->setOrder($this->getOrder())
			->setOffset($nav->getOffset())
			->setLimit($nav->getLimit())
			->countTotal(true)
		;

		$res = $query->exec();

		$nav->setRecordCount($res->getCount());
		$this->arResult['NAV'] = $nav;

		$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
		$this->arResult['ENABLE_NEXT_PAGE'] = (
			($nav->getCurrentPage() * $nav->getPageSize() + 1) <= $nav->getRecordCount()
		);

		$groups = [];
		while ($group = $res->fetch())
		{
			$groupId = $group['ID'];
			$group['PATH'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_GROUP_TASKS'],
				['group_id' => $groupId]
			);
			$groups[$groupId] = $group;
		}
		if (empty($groups))
		{
			return [];
		}

		$groups = $this->fillEfficiencies($groups);
		$groups = $this->fillMembers($groups);
		$groups = $this->fillTags($groups);

		return $groups;
	}

	private function getPrimaryProjectsQuery(): Query
	{
		$query = WorkgroupTable::query();
		$query
			->setSelect([
				'ID',
				'NAME',
				'PROJECT_DATE_START',
				'PROJECT_DATE_FINISH',
				'IMAGE_ID',
				'NUMBER_OF_MODERATORS',
				'NUMBER_OF_MEMBERS',
				'OPENED',
				'CLOSED',
				'USER_GROUP_ID' => 'UG.ID',
				'ACTIVITY_DATE',
				new ExpressionField(
					'IS_PINNED',
					ProjectUserOptionTable::getSelectExpression(
						$this->arParams['USER_ID'],
						UserOptionTypeDictionary::OPTION_PINNED
					),
					['ID', 'UG.USER_ID']
				)
			])
			->registerRuntimeField(
				'UG',
				new ReferenceField(
					'UG',
					UserToGroupTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->arParams['USER_ID']),
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				'PLA',
				new ReferenceField(
					'PLA',
					ProjectLastActivityTable::getEntity(),
					Join::on('this.ID', 'ref.PROJECT_ID'),
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				null,
				new ExpressionField('ACTIVITY_DATE', 'IFNULL(%s, %s)', ['PLA.ACTIVITY_DATE', 'DATE_UPDATE'])
			)
			->where($this->getProjectVisibilityCondition())
		;

		return $this->processGridFilter($query);
	}

	private function getProjectVisibilityCondition(): ConditionTree
	{
		if (!static::$projectVisibilityCondition)
		{
			static::$projectVisibilityCondition =
				Query::filter()
					->logic('or')
					->where('VISIBLE', 'Y')
					->where(
						Query::filter()
							->whereNotNull('UG.ID')
							->whereIn('UG.ROLE', UserToGroupTable::getRolesMember())
					)
			;
		}

		return static::$projectVisibilityCondition;
	}

	private function fillEfficiencies(array $groups): array
	{
		$efficiencies = Effective::getAverageEfficiencyForGroups(
			null,
			null,
			0,
			array_keys($groups)
		);

		foreach ($groups as $groupId => $group)
		{
			$groups[$groupId]['EFFICIENCY'] = ($efficiencies[$groupId] ?: 0);
		}

		return $groups;
	}

	private function fillMembers(array $groups): array
	{
		$groupIds = array_keys($groups);
		$members = array_fill_keys($groupIds, []);

		$query = $this->getPrimaryUsersQuery($groupIds);
		$query
			->whereIn(
				'ROLE',
				[
					UserToGroupTable::ROLE_OWNER,
					UserToGroupTable::ROLE_MODERATOR,
					UserToGroupTable::ROLE_USER,
					UserToGroupTable::ROLE_REQUEST,
				]
			)
		;

		$result = $query->exec();
		while ($member = $result->fetch())
		{
			$memberId = (int)$member['USER_ID'];
			$groupId = (int)$member['GROUP_ID'];

			$isGroupOwner = ($member['ROLE'] === UserToGroupTable::ROLE_OWNER);
			$isGroupModerator = ($member['ROLE'] === UserToGroupTable::ROLE_MODERATOR);
			$isGroupAccessRequesting = ($member['ROLE'] === UserToGroupTable::ROLE_REQUEST);
			$isGroupAccessRequestingByMe = (
				$isGroupAccessRequesting && $member['INITIATED_BY_TYPE'] === UserToGroupTable::INITIATED_BY_USER
			);
			$isHead = ($isGroupOwner || $isGroupModerator);

			$members[$groupId][($isHead ? 'HEADS' : 'MEMBERS')][$memberId] = [
				'ID' => $memberId,
				'IS_GROUP_OWNER' => ($isGroupOwner ? 'Y' : 'N'),
				'IS_GROUP_MODERATOR' => ($isGroupModerator ? 'Y' : 'N'),
				'IS_GROUP_ACCESS_REQUESTING' => ($isGroupAccessRequesting ? 'Y' : 'N'),
				'IS_GROUP_ACCESS_REQUESTING_BY_ME' => ($isGroupAccessRequestingByMe ? 'Y' : 'N'),
				'AUTO_MEMBER' => $member['AUTO_MEMBER'],
				'PHOTO' => $this->getUserPictureSrc($member['PERSONAL_PHOTO']),
				'HREF' => CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					['user_id' => $memberId]
				),
				'FORMATTED_NAME' => CUser::FormatName($this->arParams['NAME_TEMPLATE'], $member, true),
			];
		}

		foreach ($groupIds as $groupId)
		{
			$groups[$groupId]['MEMBERS'] = [
				'HEADS' => ($members[$groupId]['HEADS'] ?? []),
				'MEMBERS' => ($members[$groupId]['MEMBERS'] ?? []),
			];
		}

		return $groups;
	}

	private function getPrimaryUsersQuery(array $groupIds): Query
	{
		$query = UserToGroupTable::query();
		$query
			->setSelect([
				'GROUP_ID',
				'USER_ID',
				'ROLE',
				'INITIATED_BY_TYPE',
				'AUTO_MEMBER',
				'NAME' => 'USER.NAME',
				'LAST_NAME' => 'USER.LAST_NAME',
				'SECOND_NAME' => 'USER.SECOND_NAME',
				'LOGIN' => 'USER.LOGIN',
				'PERSONAL_PHOTO' => 'USER.PERSONAL_PHOTO',
			])
			->where('GROUP.ACTIVE', 'Y')
			->where('USER.ACTIVE', 'Y')
			->whereIn('GROUP_ID', $groupIds)
		;

		return $query;
	}

	private function getUserPictureSrc(?int $photoId): ?string
	{
		static $cache = [];

		$width = $height = 100;
		$key = "{$photoId}.{$width}.{$height}";

		if (!array_key_exists($key, $cache))
		{
			$src = false;

			if ($photoId > 0)
			{
				$imageFile = CFile::GetFileArray($photoId);
				if ($imageFile !== false)
				{
					$tmpImage = CFile::ResizeImageGet(
						$imageFile,
						[
							'width' => $width,
							'height' => $height,
						],
						BX_RESIZE_IMAGE_EXACT
					);
					$src = $tmpImage['src'];
				}

				$cache[$key] = $src;
			}
		}

		return $cache[$key];
	}

	private function fillTags(array $groups): array
	{
		$groupTags = [];

		$res = WorkgroupTagTable::getList([
			'select' => ['GROUP_ID', 'NAME'],
			'filter' => [
				'GROUP_ID' => array_keys($groups),
				'GROUP.ACTIVE' => 'Y',
			],
		]);
		while ($tag = $res->fetch())
		{
			$groupId = (int)$tag['GROUP_ID'];
			$groupTags[$groupId][] = $tag['NAME'];
		}

		foreach ($groups as $groupId => $group)
		{
			$groups[$groupId]['TAGS'] = $groupTags[$groupId];
		}

		return $groups;
	}

	private function processGridFilter(Query $query): Query
	{
		$filterFields = $this->getFilterFields();
		$filterOptions = $this->getFilterOptions();
		$filterData = ($filterOptions ? $filterOptions->getFilter($filterFields) : []);

		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData['FILTER_APPLIED'] !== true)
		{
			return $query;
		}

		if (array_key_exists('FIND', $filterData) && trim($filterData['FIND']) !== '')
		{
			$query->whereMatch('SEARCH_INDEX', trim(str_rot13($filterData['FIND'])));
		}

		foreach ($filterFields as $filterRow)
		{
			$id = $filterRow['id'];
			$type = $filterRow['type'];

			switch ($type)
			{
				case 'number':
					$this->handleNumberFilterRow($id, $filterData, $query);
					break;

				case 'string':
					$this->handleStringFilterRow($id, $filterData, $query);
					break;

				case 'date':
					$this->handleDateFilterRow($id, $filterData, $query);
					break;

				case 'list':
					$this->handleListFilterRow($id, $filterData, $query);
					break;

				case 'dest_selector':
					$this->handleEntitySelectorFilterRow($id, $filterData, $query);
					break;

				default:
					break;
			}
		}

		return $query;
	}

	private function getFilterFields(): array
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_NAME'),
				'type' => 'string',
				'default' => true,
			],
			'OWNER_ID' => [
				'id' => 'OWNER_ID',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_DIRECTOR'),
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
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_MEMBER'),
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
			'IS_PROJECT' => [
				'id' => 'IS_PROJECT',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_IS_PROJECT'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_IS_PROJECT_Y'),
					'N' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_IS_PROJECT_N'),
				],
			],
			'TYPE' => [
				'id' => 'TYPE',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_TYPE'),
				'type' => 'list',
				'items' => $this->getProjectTypes(),
			],
			'PROJECT_DATE' => [
				'id' => 'PROJECT_DATE',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_PROJECT_DATE'),
				'type' => 'date',
			],
			'CLOSED' => [
				'id' => 'CLOSED',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_CLOSED'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_CLOSED_Y'),
					'N' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_CLOSED_N'),
				],
			],
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_ID'),
				'type' => 'number',
				'default' => false,
			],
			'TAGS' => [
				'id' => 'TAGS',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_TAG'),
				'type' => 'string',
				'default' => false,
			],
			'COUNTERS' => [
				'id' => 'COUNTERS',
				'name' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_COUNTERS'),
				'type' => 'list',
				'items' => [
					'EXPIRED' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_COUNTERS_EXPIRED'),
					'NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_COUNTERS_NEW_COMMENTS'),
					'PROJECT_EXPIRED' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_COUNTERS_PROJECT_EXPIRED'),
					'PROJECT_NEW_COMMENTS' => Loc::getMessage('TASKS_PROJECTS_FILTER_COLUMN_COUNTERS_PROJECT_NEW_COMMENTS'),
				],
			],
		];
	}

	private function getProjectTypes(): array
	{
		static $types = [];

		if (empty($types))
		{
			$types = Workgroup::getTypes([
				'category' => ['projects', 'groups'],
			]);
		}

		return $types;
	}

	private function getFilterOptions(): ?Filter\Options
	{
		static $filterOptions = null;

		if (!$filterOptions)
		{
			$filterOptions = new Filter\Options($this->arParams['GRID_ID'], $this->getPresetFields());
		}

		return $filterOptions;
	}

	private function getPresetFields(): array
	{
		return [
			'my' => [
				'name' => Loc::getMessage('TASKS_PROJECTS_PRESET_MY'),
				'fields' => [
					'CLOSED' => 'N',
					'MEMBER_ID' => $this->arParams['USER_ID'],
					'MEMBER_ID_label' => $this->getCurrentUserName(),
				],
				'default' => true,
			],
			'active_project' => [
				'name' => Loc::getMessage('TASKS_PROJECTS_PRESET_ACTIVE_PROJECT'),
				'fields' => [
					'CLOSED' => 'N',
				],
				'default' => false,
			],
			'inactive_project' => [
				'name' => Loc::getMessage('TASKS_PROJECTS_PRESET_INACTIVE_PROJECT'),
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
			['ID_EQUAL_EXACT' => $this->arParams['USER_ID']],
			['FIELDS' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN']]
		);
		if ($user = $result->fetch())
		{
			return CUser::FormatName($this->arParams['NAME_TEMPLATE'], $user, true, false);
		}

		return '';
	}

	private function handleNumberFilterRow($id, $filterData, Query $query): void
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
	}

	private function handleStringFilterRow($id, $filterData, Query $query): void
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return;
		}

		if ($id === 'TAGS')
		{
			$query
				->registerRuntimeField(
					'WT',
					new ReferenceField(
						'WT',
						WorkgroupTagTable::getEntity(),
						Join::on('this.ID', 'ref.GROUP_ID'),
						['join_type' => 'left']
					)
				)
				->where('WT.NAME', $filterData[$id])
			;
		}
		else
		{
			$query->whereLike($id, $filterData[$id]);
		}
	}

	private function handleDateFilterRow($id, $filterData, Query $query): void
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
	}

	private function handleListFilterRow($id, $filterData, Query $query): void
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return;
		}

		if ($id === 'CLOSED')
		{
			$query->where('CLOSED', $filterData[$id]);
		}
		else if ($id === 'IS_PROJECT')
		{
			$query->where('PROJECT', $filterData[$id]);
		}
		else if ($id === 'TYPE')
		{
			$types = $this->getProjectTypes();
			$typeName = $filterData[$id];
			$type = $types[$typeName];

			if ($type)
			{
				$condition =
					Query::filter()
						->where('OPENED', $type['OPENED'])
						->where('VISIBLE', $type['VISIBLE'])
						->where('PROJECT', $type['PROJECT'])
				;
				if ($type['EXTERNAL'] !== 'N')
				{
					$condition->where('SITE_ID', CExtranet::GetExtranetSiteID());
				}

				$query->where($condition);
			}
		}
		elseif ($id === 'COUNTERS')
		{
			$query->getFilterHandler()->removeCondition($this->getProjectVisibilityCondition());
			$query
				->setDistinct(true)
				->registerRuntimeField(
					'TS',
					new ReferenceField(
						'TS',
						Counter\CounterTable::getEntity(),
						Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->arParams['USER_ID']),
						['join_type' => 'inner']
					)
				)
				->where(
					Query::filter()
						->whereNotNull('UG.ID')
						->whereIn('UG.ROLE', UserToGroupTable::getRolesMember())
				)
			;

			$typesMap = [
				'EXPIRED' => [
					'INCLUDE' => Counter\CounterDictionary::MAP_EXPIRED,
					'EXCLUDE' => null,
				],
				'NEW_COMMENTS' => [
					'INCLUDE' => Counter\CounterDictionary::MAP_COMMENTS,
					'EXCLUDE' => null,
				],
				'PROJECT_EXPIRED' => [
					'INCLUDE' => array_merge(
						[Counter\CounterDictionary::COUNTER_GROUP_EXPIRED],
						Counter\CounterDictionary::MAP_MUTED_EXPIRED
					),
					'EXCLUDE' => Counter\CounterDictionary::MAP_EXPIRED,
				],
				'PROJECT_NEW_COMMENTS' => [
					'INCLUDE' => array_merge(
						[Counter\CounterDictionary::COUNTER_GROUP_COMMENTS],
						Counter\CounterDictionary::MAP_MUTED_COMMENTS
					),
					'EXCLUDE' => Counter\CounterDictionary::MAP_COMMENTS,
				],
			];
			$type = $filterData[$id];

			$condition = Query::filter()->whereIn('TS.TYPE', $typesMap[$type]['INCLUDE']);
			if ($typesMap[$type]['EXCLUDE'])
			{
				$typesToExclude = "('" . implode("','", $typesMap[$type]['EXCLUDE']) . "')";
				$query->registerRuntimeField(
					'EXCLUDED_COUNTER_EXISTS',
					new ExpressionField(
						'EXCLUDED_COUNTER_EXISTS',
						"(
							SELECT 1
							FROM b_tasks_scorer 
							WHERE GROUP_ID = %s
							  AND TASK_ID = %s
							  AND USER_ID = {$this->arParams['USER_ID']}
							  AND TYPE IN {$typesToExclude}
							LIMIT 1
						)",
						['ID', 'TS.TASK_ID']
					)
				);
				$condition->whereNull('EXCLUDED_COUNTER_EXISTS');
			}

			$query->where($condition);
		}
	}

	private function handleEntitySelectorFilterRow($id, $filterData, Query $query): void
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return;
		}

		if ($id === 'OWNER_ID')
		{
			$query->whereIn('OWNER_ID', $filterData[$id]);
		}
		elseif ($id === 'MEMBER_ID')
		{
			$query
				->setDistinct(true)
				->registerRuntimeField(
					'UG2',
					new ReferenceField(
						'UG2',
						UserToGroupTable::getEntity(),
						Join::on('this.ID', 'ref.GROUP_ID'),
						['join_type' => 'left']
					)
				)
				->whereIn('UG2.USER_ID', $filterData[$id])
				->whereNotNull('UG2.ID')
				->whereIn('UG2.ROLE', UserToGroupTable::getRolesMember())
			;
		}
	}

	private function doPostAction(): void
	{
		if (!$this->request->isAjaxRequest())
		{
			if (count($this->arResult['GROUPS']) > 0)
			{
				$popupData = [];
				$showTour = false;
			}
			else
			{
				/** @var TourGuide\FirstProjectCreation $firstProjectCreationTour */
				$firstProjectCreationTour = TourGuide\FirstProjectCreation::getInstance($this->arParams['USER_ID']);
				$popupData = $firstProjectCreationTour->getCurrentStepPopupData();
				$showTour = $firstProjectCreationTour->proceed();
			}

			$this->arResult['TOURS'] = [
				'firstProjectCreation' => [
					'popupData' => $popupData,
					'show' => $showTour,
				],
			];

			if ($showTour)
			{
				\Bitrix\Tasks\AnalyticLogger::logToFile(
					'markShowedStep',
					'firstProjectCreation',
					'0',
					'tourGuide'
				);
			}
		}
	}

	public function executeComponent()
	{
		$this->arResult['ERRORS'] = [];

		if (Loader::includeModule('tasks'))
		{
			$this->errors = new Collection();

			if (
				$this->checkModules()
				&& $this->checkPermissions()
			)
			{
				$this->checkParameters();
				$this->doPreAction();
				$this->getData();
				$this->doPostAction();
			}

			$this->arResult['ERRORS'] = $this->errors->getArrayMeta();
		}
		else
		{
			$this->arResult['ERRORS'][] = [
				'CODE' => 'TASKS_MODULE_NOT_INSTALLED',
				'MESSAGE' => 'Tasks module is not installed',
			];
		}

		$this->includeComponentTemplate();
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'USER_ID',
			'GRID_ID',
			'PATH_TO_USER',
			'PATH_TO_USER_TASKS',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW',
			'PATH_TO_USER_TASKS_TEMPLATES',
			'PATH_TO_USER_LEAVE_GROUP',
			'PATH_TO_USER_REQUEST_GROUP',
			'PATH_TO_USER_REQUESTS',
			'PATH_TO_GROUP',
			'PATH_TO_GROUP_CREATE',
			'PATH_TO_GROUP_EDIT',
			'PATH_TO_GROUP_DELETE',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_TASKS',
			'PATH_TO_TASKS_REPORT_CONSTRUCT',
			'PATH_TO_TASKS_REPORT_VIEW',
			'PATH_TO_REPORTS',
			'NAME_TEMPLATE',
		];
	}

	private function checkRequirementsForAjaxCalls(): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		$this->errors = new Collection();

		return $this->checkModules() && $this->checkPermissions();
	}

	public function processActionAction(string $action, array $ids, array $data = []): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$result = [];

		switch ($action)
		{
			case 'pin':
				foreach ($ids as $groupId)
				{
					UserOptionController::getInstance($this->arParams['USER_ID'], $groupId)
						->add(UserOptionTypeDictionary::OPTION_PINNED)
					;
				}
				break;

			case 'unpin':
				foreach ($ids as $groupId)
				{
					UserOptionController::getInstance($this->arParams['USER_ID'], $groupId)
						->delete(UserOptionTypeDictionary::OPTION_PINNED)
					;
				}
				break;

			case 'request':
				$result = $this->request($ids);
				break;

			case 'addToArchive':
				$result = Group::addToArchive($ids);
				break;

			case 'removeFromArchive':
				$result = Group::removeFromArchive($ids);
				break;

			case 'update':
				$result = Group::update($ids, $data);
				break;

			case 'delete':
				$result = Group::delete($ids);
				break;

			default:
				break;
		}

		return $result;
	}

	private function request(array $ids): array
	{
		return [];
	}

	public function getPopupMembersAction(int $groupId, string $type, int $page): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$members = [];

		$rolesMap = [
			'all' => [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR, UserToGroupTable::ROLE_USER],
			'heads' => [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR],
			'members' => [UserToGroupTable::ROLE_USER],
		];
		$limit = 10;

		$query = $this->getPrimaryUsersQuery([$groupId]);
		$query
			->whereIn('ROLE', $rolesMap[$type])
			->setLimit($limit)
			->setOffset(($page - 1) * $limit)
		;

		$result = $query->exec();
		while ($member = $result->fetch())
		{
			$id = $member['USER_ID'];
			$members[] = [
				'ID' => $id,
				'PHOTO' => $this->getUserPictureSrc($member['PERSONAL_PHOTO']),
				'HREF' => CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					['user_id' => $id]
				),
				'FORMATTED_NAME' => CUser::FormatName($this->arParams['NAME_TEMPLATE'], $member, true),
			];
		}

		return $members;
	}

	public function checkExistenceAction(array $groupIds): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		return $this->getGroupsData($groupIds);
	}

	public function prepareGridRowsAction(array $groupIds, array $data = []): array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return [];
		}

		$groups = (empty($data) ? $this->getGroupsData($groupIds) : $data);
		$groups = $this->fillEfficiencies($groups);
		$groups = $this->fillMembers($groups);
		$groups = $this->fillTags($groups);

		return (new Bitrix\Tasks\Grid\Project\Grid($groups, $this->arParams))->prepareRows();
	}

	private function getGroupsData(array $groupIds): array
	{
		$groups = array_fill_keys($groupIds, false);

		$query = $this->getPrimaryProjectsQuery();
		$query->whereIn('ID', $groupIds);

		$result = $query->exec();
		while ($group = $result->fetch())
		{
			$groupId = $group['ID'];
			$group['PATH'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_GROUP_TASKS'],
				['group_id' => $groupId]
			);
			$groups[$groupId] = $group;
		}

		return $groups;
	}

	public function findProjectPlaceAction(int $groupId, int $currentPage): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$query = $this->getPrimaryProjectsQuery();
		$query
			->setSelect([
				'ID',
				'ACTIVITY_DATE',
				new ExpressionField(
					'IS_PINNED',
					ProjectUserOptionTable::getSelectExpression(
						$this->arParams['USER_ID'],
						UserOptionTypeDictionary::OPTION_PINNED
					),
					['ID', 'UG.USER_ID']
				)
			])
			->setOrder($this->getOrder())
			->setLimit($currentPage * 10)
		;

		$projects = $query->exec()->fetchAll();
		$projects = array_map('intval', array_column($projects, 'ID'));

		if (empty($projects) || ($index = array_search($groupId, $projects, true)) === false)
		{
			return [
				'projectBefore' => false,
				'projectAfter' => false,
			];
		}

		return [
			'projectBefore' => ($index === 0 ? 0 : $projects[$index - 1]),
			'projectAfter' => ($index === count($projects) - 1 ? 0 : $projects[$index + 1]),
		];
	}
}