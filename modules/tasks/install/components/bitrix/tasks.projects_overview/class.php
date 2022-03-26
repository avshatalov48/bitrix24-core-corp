<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * @Deprecated since tasks 22.400.0
 */
class TasksProjectsOverviewComponent extends TasksBaseComponent
{

	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$errors->add(
				'SOCIALNETWORK_MODULE_NOT_INSTALLED',
				Loc::getMessage("TASKS_TL_SOCIALNETWORK_MODULE_NOT_INSTALLED")
			);
		}

		return $errors->checkNoFatals();
	}

	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		return $errors->checkNoFatals();
	}

	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		$currentUserId = (int)$arResult['USER_ID'];
		$viewedUserId = (int)$arParams['USER_ID'];

		if ($currentUserId !== $viewedUserId && !User::isSuper($currentUserId))
		{
			$errors->add(
				'ACCESS_DENIED',
				Loc::getMessage('TASKS_PROJECT_OVERVIEW_COMPONENT_ACCESS_DENIED')
			);
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		$this->arParams['GRID_ID'] = 'TASKS_GRID_PROJECTS_OVERVIEW';
		$this->arParams['PATH_TO_GROUP_ADD'] = \CComponentEngine::makePathFromTemplate(
			'/company/personal/user/#user_id#/groups/#action#/?firstRow=project',
			[
				'user_id' => $this->arParams['USER_ID'],
				'action' => 'create',
			]
		);
		$this->arParams['PATH_TO_USER_TASKS_TASK'] = \CComponentEngine::makePathFromTemplate(
			'/company/personal/user/#user_id#/tasks/task/#action#/0/',
			[
				'user_id' => $this->arParams['USER_ID'],
				'action' => 'edit',
			]
		);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['GROUPS'] = $this->getGroups();
		$this->arParams['HEADERS'] = $this->getGridHeader();
		$this->arParams['FILTERS'] = $this->getFilterFields();
		$this->arResult['JS_DATA']['customFields'] = $this->getCustomFields();
		$this->arParams['PRESETS'] = $this->getPresetFields();
	}

	private static function getUserPictureSrc($photoId, $gender = '?', $width = 100, $height = 100)
	{
		static $cache = array();

		$key = $photoId.'.'.$width.'.'.$height;

		if (!array_key_exists($key, $cache))
		{
			$src = false;

			if ($photoId > 0)
			{
				$imageFile = \CFile::GetFileArray($photoId);
				if ($imageFile !== false)
				{
					$tmpImage = \CFile::ResizeImageGet(
						$imageFile,
						array("width" => $width, "height" => $height),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $tmpImage["src"];
				}

				$cache[$key] = $src;
			}
		}

		return $cache[$key];
	}

	private function getGroups()
	{
		$filter = $this->getGridFilter();

		if (array_key_exists('MEMBERS', $filter))
		{
			$filter['USER_ID'] = $filter['MEMBERS'];
		}

		$filter['=USER_ID'] = $this->arParams["USER_ID"];
		$filter['ROLE'] = UserToGroupTable::getRolesMember();

		//region GROUPS
		$nav = new \Bitrix\Main\UI\PageNavigation("nav-projects");
		$nav->allowAllRecords(true)
			->setPageSize(10)
			->initFromUri();

		$res = UserToGroupTable::getList(
			array(
				'filter' => $filter,
				'count_total' => true,
				"offset" => $nav->getOffset(),
				"limit" => $nav->getLimit(),
				'select' => array(
					'GROUP_ID',
					'NAME' => 'GROUP.NAME',
					'PROJECT_DATE_START' => 'GROUP.PROJECT_DATE_START',
					'PROJECT_DATE_FINISH' => 'GROUP.PROJECT_DATE_FINISH',
					'IMAGE_ID' => 'GROUP.IMAGE_ID',
					'NUMBER_OF_MEMBERS' => 'GROUP.NUMBER_OF_MEMBERS',
					'CLOSED' => 'GROUP.CLOSED'
				)
			)
		);

		$nav->setRecordCount($res->getCount());
		$this->arResult['NAV'] = $nav;

		$groups = $res->fetchAll();
		if (empty($groups))
		{
			return array();
		}

		$groupIds = array();
		foreach ($groups as $group)
		{
			$groupIds[] = $group['GROUP_ID'];
		}
		//endregion

		//TODO REFACTOR!

		//region MEMBERS
		$res = UserToGroupTable::getList(
			array(
				'filter' => array(
					'GROUP_ID' => $groupIds,
					'GROUP.ACTIVE' => 'Y',
					'GROUP.CLOSED' => 'N',
					'USER.ACTIVE' => 'Y',
					'ROLE' => array(
						\Bitrix\Socialnetwork\UserToGroupTable::ROLE_OWNER,
						\Bitrix\Socialnetwork\UserToGroupTable::ROLE_MODERATOR,
						\Bitrix\Socialnetwork\UserToGroupTable::ROLE_USER
					)
				),
				'select' => array(
					'GROUP_ID',
					'USER_ID',
					'ROLE',
					'GROUP_OWNER_ID' => 'GROUP.OWNER_ID',
					'USER_PERSONAL_PHOTO' => 'USER.PERSONAL_PHOTO'
				)
			)
		);

		$members = $res->fetchAll();
		$groupMembers = array();
		foreach ($members as $member)
		{
			$memberId = (int)$member['USER_ID'];
			$groupId = $member['GROUP_ID'];
			$isGroupOwner = ($memberId == $member['GROUP_OWNER_ID']);
			$isGroupModerator = ($member['ROLE'] == SONET_ROLES_MODERATOR);

			$isHead = $isGroupOwner || $isGroupModerator;

			$groupMembers[$groupId][$isHead ? 'HEADS' : 'MEMBERS'][] = array(
				'ID' => $memberId,
				'IS_GROUP_OWNER' => ($isGroupOwner ? 'Y' : 'N'),
				'IS_GROUP_MODERATOR' => ($isGroupModerator ? 'Y' : 'N'),
				'PHOTO_ID' => $member['USER_PERSONAL_PHOTO'],
				'PHOTO' => self::getUserPictureSrc($member['USER_PERSONAL_PHOTO']),
				'USER_NAME' => $member['USER_NAME'],
				'USER_LAST_NAME' => $member['USER_LAST_NAME'],
				'USER_SECOND_NAME' => $member['USER_SECOND_NAME'],
				'USER_LOGIN' => $member['USER_LOGIN'],
				'WORK_POSITION' => (string)$member['USER_WORK_POSITION'],
				'HREF' => CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $memberId)
				),
				'USER_GENDER' => $member['USER_PERSONAL_GENDER'],
				'FORMATTED_NAME' => $this->getFormattedUserName(
					$memberId
				)
			);

			$this->arResult['JS_DATA']['members-list'][$groupId] = $groupMembers[$groupId]['MEMBERS'];
		}
		//endregion

		//region COUNTERS && MAIN COMPILE
		$groupCounters = $this->getCounters($groupIds);

		foreach ($groups as $key => &$group)
		{
			$groupId = $group['GROUP_ID'];

			$counters = $groupCounters[$groupId];
			//			if ($counters['ALL'] == 0)    // Skip groups without tasks
			//			{
			//				unset($groups[$key]);
			//				continue;
			//			}
			$group['COUNTERS'] = $counters;

			$groupPath = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_GROUP_TASKS'],
				array('group_id' => $groupId)
			);
			$groupPathToTask = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_GROUP_TASKS'],
					array('group_id' => $groupId)
				).'?apply_filter=Y';

			$group['PATHES'] = array(
				'TO_GROUP' => $groupPath,
				'ALL' => $groupPathToTask.'&clear_filter=Y',
				'IN_WORK' => $groupPathToTask.'&STATUS[]=2&STATUS[]=3',
				'COMPLETE' => $groupPathToTask.'&STATUS[]=5',
			);

			$group['MEMBERS'] = $groupMembers[$groupId];
		}

		//endregion

		return $groups;
	}

	/**
	 * @return array
	 */
	private function getGridFilter(): array
	{
		$filter = [];

		$filterFields = $this->getFilterFields();
		$filterOptions = $this->getFilterOptions();
		$filterData = ($filterOptions ? $filterOptions->getFilter($filterFields) : []);

		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData['FILTER_APPLIED'] !== true)
		{
			return $filter;
		}

		if (array_key_exists('FIND', $filterData) && $filterData['FIND'] !== '')
		{
			$filter['*%GROUP.SEARCH_INDEX'] = trim(str_rot13($filterData['FIND']));
		}

		foreach ($filterFields as $filterRow)
		{
			$id = $filterRow['id'];
			$type = $filterRow['type'];

			switch ($type)
			{
				case 'number':
					$filter = $this->handleNumberFilterRow($id, $filterData, $filter);
					break;

				case 'string':
					if (array_key_exists($id, $filterData) && $filterData[$id] !== '')
					{
						$filter['%GROUP.'.$id] = $filterData[$id];
					}
					break;

				case 'date':
					$filter = $this->handleDateFilterRow($id, $filterData, $filter);
					break;

				case 'list':
					$filter = $this->handleListFilterRow($id, $filterData, $filter);
					break;

				case 'custom_entity':
					if (array_key_exists($id, $filterData) && !empty($filterData[$id]))
					{
						$filter['GROUP.OWNER_ID'] = $filterData[$id];
					}
					break;

				default:
					break;
			}
		}

		return $filter;
	}

	/**
	 * @param $id
	 * @param $filterData
	 * @param $filter
	 * @return array
	 */
	private function handleNumberFilterRow($id, $filterData, $filter): array
	{
		$from = $id.'_from';
		$to = $id.'_to';
		$less = '<='.$id;
		$more = '>='.$id;

		if (array_key_exists($from, $filterData) && !empty($filterData[$from]))
		{
			$filter[$more] = $filterData[$from];
		}
		if (array_key_exists($to, $filterData) && !empty($filterData[$to]))
		{
			$filter[$less] = $filterData[$to];
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

		return $filter;
	}

	/**
	 * @param $id
	 * @param $filterData
	 * @param $filter
	 * @return array
	 */
	private function handleDateFilterRow($id, $filterData, $filter): array
	{
		$from = $id.'_from';
		$to = $id.'_to';

		if (array_key_exists($from, $filterData) && !empty($filterData[$from]))
		{
			$filter['>=GROUP.'.$id.'_START'] = $filterData[$from];
		}
		if (array_key_exists($to, $filterData) && !empty($filterData[$to]))
		{
			$filter['<=GROUP.'.$id.'_FINISH'] = $filterData[$to];
		}

		return $filter;
	}

	/**
	 * @param $id
	 * @param $filterData
	 * @param $filter
	 * @return array
	 */
	private function handleListFilterRow($id, $filterData, $filter): array
	{
		if (!array_key_exists($id, $filterData) || empty($filterData[$id]))
		{
			return $filter;
		}

		if ($id === 'CLOSED')
		{
			$filter['GROUP.CLOSED'] = $filterData[$id];
		}
		else if ($id === 'TYPE')
		{
			$typeName = $filterData[$id];
			$types = $this->getProjectTypes();
			$type = $types[$typeName];

			if ($type)
			{
				$filter['GROUP.VISIBLE'] = $type['VISIBLE'];
				$filter['GROUP.OPENED'] = $type['OPENED'];
				$filter['GROUP.PROJECT'] = $type['PROJECT'];

				if ($type['EXTERNAL'] !== 'N')
				{
					$filter['GROUP.SITE_ID'] = CExtranet::GetExtranetSiteID();
				}
			}
		}

		return $filter;
	}

	/**
	 * @return Filter\Options|null
	 */
	private function getFilterOptions(): ?Filter\Options
	{
		static $filterOptions = null;

		if (!$filterOptions)
		{
			$filterOptions = new Filter\Options($this->arParams['GRID_ID'], $this->getPresetFields());
		}

		return $filterOptions;
	}

	/**
	 * @return array
	 */
	private function getPresetFields(): array
	{
		return [
			'active_project' => [
				'name' => Loc::getMessage('TASKS_PRESET_ACTIVE_PROJECT'),
				'default' => true,
				'fields' => [
					'CLOSED' => 'N',
				],
			],
			'inactive_project' => [
				'name' => Loc::getMessage('TASKS_PRESET_INACTIVE_PROJECT'),
				'default' => false,
				'fields' => [
					'CLOSED' => 'Y',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	private function getFilterFields(): array
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_NAME'),
				'type' => 'string',
				'default' => true,
			],
			'OWNER_ID' => [
				'id' => 'OWNER_ID',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_MEMBERS'),
				'type' => 'custom_entity',
				'params' => ['multiple' => 'Y'],
				'selector' => [
					'type' => 'user',
					'data' => [
						'id' => 'user',
						'fieldId' => 'OWNER_ID',
					],
				],
				'default' => true,
			],
			'PROJECT_DATE' => [
				'id' => 'PROJECT_DATE',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_PROJECT_DATE'),
				'type' => 'date',
			],
			'TYPE' => [
				'id' => 'TYPE',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_TYPE'),
				'type' => 'list',
				'items' => $this->getProjectTypes(),
			],
			'CLOSED' => [
				'id' => 'CLOSED',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_CLOSED'),
				'type' => 'list',
				'items' => [
					'Y' => Loc::getMessage('TASKS_FILTER_COLUMN_CLOSED_Y'),
					'N' => Loc::getMessage('TASKS_FILTER_COLUMN_CLOSED_N'),
				],
			],
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_ID'),
				'type' => 'number',
				'default' => false,
			],
			'KEYWORDS' => [
				'id' => 'KEYWORDS',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_TAG'),
				'type' => 'string',
				'default' => false,
			],
		];
	}

	/**
	 * @return array
	 */
	private function getProjectTypes(): array
	{
		static $types = [];

		if (empty($types))
		{
			$types = Workgroup::getTypes([
				'currentExtranetSite' => $this->isExtranetSite(),
				'category' => ['projects', 'groups'],
			]);
		}

		return $types;
	}

	/**
	 * @return bool
	 */
	private function isExtranetSite(): bool
	{
		return (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite());
	}

	private function getFormattedUserName($id/*, $name, $secondName, $lastName, $login*/)
	{
		static $cache = array();

		if (array_key_exists($id, $cache))
		{
			$formattedName = $cache[$id];
		}
		else
		{
			$resUser = CUser::GetByID($id);
			$arUser = $resUser->Fetch();

			$formattedName = \CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				$arUser,
				true,
				true
			);

			$cache[$id] = $formattedName;
		}

		return $formattedName;
	}

	private function getCounters(array $groupIds)
	{
		$counters = array();

		$filterInstance = CTaskFilterCtrl::getInstance($this->arParams['USER_ID'], true);

		$filterAll = $filterInstance->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS);
		$filterInWork = $filterInstance->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_ACTIVE_MY_TASKS);
		$filterComplete = $filterInstance->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_COMPLETED_MY_TASKS);
		$filterInWorkExpired = $filterInWork;
		$filterInWorkExpired['<DEADLINE'] = ConvertTimeStamp(time() + CTasksTools::getTimeZoneOffset(), 'FULL');

		$filterAll['GROUP_ID'] = $groupIds;
		$filterInWork['GROUP_ID'] = $groupIds;
		$filterComplete['GROUP_ID'] = $groupIds;
		$filterInWorkExpired['GROUP_ID'] = $groupIds;

		$map = array(
			'ALL' => &$filterAll,
			'IN_WORK' => &$filterInWork,
			'COMPLETE' => &$filterComplete,
			'EXPIRED' => &$filterInWorkExpired
		);

		$datesRange = Effective::getDatesRange();

		foreach ($groupIds as $groupId)
		{
			$counters[$groupId] = [
				'ALL' => 0,
				'IN_WORK' => 0,
				'EXPIRED' => 0,
				'COMPLETE' => 0,
				'EFFECTIVE' => Effective::getAverageEfficiency($datesRange['FROM'], $datesRange['TO'], 0, $groupId)
			];
		}

		foreach ($map as $key => &$arFilter)
		{
			$rs = CTasks::GetCount(
				$arFilter,
				array(
					'bSkipUserFields' => true,
					'bSkipExtraTables' => true,
					'bSkipJoinTblViewed' => false
				),
				array('GROUP_ID')        // group by
			);

			while ($item = $rs->fetch())
			{
				$counters[$item['GROUP_ID']][$key] = $item['CNT'];
			}
		}

		return $counters;
	}

	private function getGridHeader()
	{
		return array(
			'PROJECT' => array(
				'id' => 'PROJECT',
				'name' => GetMessage('TASKS_COLUMN_PROJECT'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			'EFFECTIVE' => array(
				'id' => 'EFFECTIVE',
				'name' => GetMessage('TASKS_COLUMN_EFFECTIVE'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			'PROJECT_DATE_START' => array(
				'id' => 'PROJECT_DATE_START',
				'name' => GetMessage('TASKS_COLUMN_PROJECT_DATE_START'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			'PROJECT_DATE_FINISH' => array(
				'id' => 'PROJECT_DATE_FINISH',
				'name' => GetMessage('TASKS_COLUMN_PROJECT_DATE_FINISH'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			//			'EFFECTIVE' => array(
			//				'id' => 'EFFECTIVE',
			//				'name' => GetMessage('TASKS_COLUMN_EFFECTIVE'),
			//				'sort' => false,
			//				'type' => 'custom',
			//				'editable' => false,
			//				'default' => true
			//			),
			'IN_WORK' => array(
				'id' => 'IN_WORK',
				'name' => GetMessage('TASKS_COLUMN_IN_WORK'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			'COMPLETE' => array(
				'id' => 'COMPLETE',
				'name' => GetMessage('TASKS_COLUMN_COMPLETE'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			),
			'ALL' => array(
				'id' => 'ALL',
				'name' => GetMessage('TASKS_COLUMN_ALL'),
				'sort' => false,
				'type' => 'custom',
				'editable' => false,
				'default' => true
			)
		);
	}

	private function getCustomFields()
	{
		static $list = array();

		if (!$list)
		{
			foreach ($this->getFilterFields() as $item)
			{
				if ($item['type'] == 'custom_entity')
				{
					$selector = $item['selector'];
					$selectorData = $selector['data'];
					$selectorData['mode'] = $selector['type'];
					$selectorData['multi'] = array_key_exists('params', $item) &&
						array_key_exists('multiple', $item['params']) &&
						$item['params']['multiple'] == 'Y';

					$list[] = $selectorData;
				}
			}
		}

		return $list;
	}

}