<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;

\Bitrix\Main\Loader::includeModule('socialnetwork');

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksInterfaceFilterComponent extends TasksBaseComponent
{
	/**
	 * Gets sprints of current groups.
	 * @return array
	 */
	protected function getSprints()
	{
		$sprints = [];

		if (
			$this->arParams['GROUP_ID'] &&
			$this->arParams['SPRINT_ID']//mark that we are in sprint mode
		)
		{
			if ($this->arResult['IS_SCRUM_PROJECT'])
			{
				$sprintService = new SprintService();
				$listSprints = $sprintService->getSprintsByGroupId($this->arParams['GROUP_ID']);
				foreach ($listSprints as $sprint)
				{
					if ($sprint->isCompletedSprint())
					{
						$sprints[$sprint->getId()] = [
							'ID' => $sprint->getId(),
							'NAME' => $sprint->getName(),
							'START_TIME' => $sprint->getDateStart()->format(Bitrix\Main\Type\Date::getFormat()),
							'FINISH_TIME' => $sprint->getDateEnd()->format(Bitrix\Main\Type\Date::getFormat()),
						];
					}
				}
			}
		}

		return $sprints;
	}

	protected function checkParameters()
	{
		$groupId = (isset($this->arParams['MENU_GROUP_ID'])? $this->arParams['MENU_GROUP_ID'] : $this->arParams['GROUP_ID']);

		static::tryParseStringParameter(
			$this->arParams['PATH_TO_USER_TASKS_TEMPLATES'],
			'/company/personal/user/#user_id#/tasks/templates/'
		);
		static::tryParseStringParameter(
			$this->arParams['PATH_TO_USER_TASKS_TEMPLATES_ACTION'],
			'/company/personal/user/#user_id#/tasks/templates/template/#action#/#template_id#/'
		);
		static::tryParseStringParameter($this->arParams['USE_EXPORT'], 'Y');
		static::tryParseStringParameter($this->arParams['USE_GROUP_BY_SUBTASKS'], 'N');
		static::tryParseStringParameter($this->arParams['USE_GROUP_BY_GROUPS'], 'N');
		static::tryParseStringParameter($this->arParams['USE_LIVE_SEARCH'], 'Y');
		static::tryParseStringParameter($this->arParams['SHOW_QUICK_FORM_BUTTON'], 'Y');
		static::tryParseStringParameter($this->arParams['SHOW_USER_SORT'], 'N');
		static::tryParseStringParameter($this->arParams['USE_GROUP_SELECTOR'], 'N');
		static::tryParseStringParameter($this->arParams['PROJECT_VIEW'], 'N');
		static::tryParseIntegerParameter($this->arParams['GROUP_SELECTOR_LIMIT'], 5);
		static::tryParseIntegerParameter($this->arParams['GROUP_ID'], 0);
		static::tryParseIntegerParameter($this->arParams['SPRINT_ID'], 0);
		static::tryParseArrayParameter($this->arParams['POPUP_MENU_ITEMS']);
		static::tryParseStringParameter(
			$this->arParams['SHOW_CREATE_TASK_BUTTON'],
			($this->canCreateGroupTasks($groupId)? 'Y' : 'N')
		);

		$isLimitExceeded = FilterLimit::isLimitExceeded();
		if ($isLimitExceeded)
		{
			$this->arResult['LIMIT_EXCEEDED'] = true;
			$this->arResult['LIMITS'] = FilterLimit::prepareStubInfo();
		}
		else if (($limitWarningValue = FilterLimit::getLimitWarningValue($this->arParams['USER_ID'])))
		{
			FilterLimit::notifyLimitWarning($this->arParams['USER_ID'], $limitWarningValue);
		}

		$group = Bitrix\Socialnetwork\Item\Workgroup::getById($this->arParams['GROUP_ID']);
		$this->arResult['IS_SCRUM_PROJECT'] = ($group && $group->isScrumProject());

		$this->arResult['SPRINTS'] = $this->getSprints();
	}

	protected function canCreateGroupTasks($groupId)
	{
		$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $groupId);
		if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
		{
			return false;
		}
		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
			SONET_ENTITY_GROUP,
			array($groupId),
			'tasks',
			'create_tasks'
		);
		$bCanCreateTasks = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];

		if (!$bCanCreateTasks)
		{
			return false;
		}

		return true;
	}

	protected function doPreAction()
	{
		// Attention: arResult['USER_ID'] -- current auth user, need for add task, arParams['USER_ID'] - current selected user!
		$this->arResult['USER_ID'] = \Bitrix\Tasks\Util\User::getId();

		if (
			$this->arParams['USE_GROUP_SELECTOR'] === 'Y'
			|| $this->arParams['PROJECT_VIEW'] === 'Y'
		)
		{
			$context = \Bitrix\Main\Application::getInstance()->getContext();
			$request = $context->getRequest();
			$selectGroup = $request->get('select_group');

			$limit = $this->arParams['GROUP_SELECTOR_LIMIT'];
			$myGroups = array();

			$currentGroup = $this->arParams['GROUP_ID'];

			if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
			{
				// show last viewed groups
				$res = \Bitrix\Socialnetwork\WorkgroupViewTable::getList(
					array(
						'select' => array(
							'GROUP_ID',
							'GROUP_NAME' => 'GROUP.NAME'
						),
						'filter' => array(
							'USER_ID'       => $this->arResult['USER_ID'],
							'=GROUP.ACTIVE' => 'Y',
							'=GROUP.CLOSED' => 'N'
						),
						'order'  => array(
							'DATE_VIEW' => 'DESC'
						)
					)
				);
				while ($row = $res->fetch())
				{
					if ($this->canReadGroupTasks($row['GROUP_ID']))
					{
						$myGroups[$row['GROUP_ID']] = $row['GROUP_NAME'];
					}
					if (count($myGroups) >= $limit)
					{
						break;
					}
				}
				// if we don't get limit, get more by date activity
				if (count($myGroups) < $limit)
				{
					$res = \CSocNetUserToGroup::GetList(
						array(
							'GROUP_DATE_ACTIVITY' => 'DESC'
						),
						array(
							'USER_ID'      => $this->arResult['USER_ID'],
							'!ROLE'        => array(
								SONET_ROLES_BAN,
								SONET_ROLES_REQUEST
							),
							'USER_ACTIVE'  => 'Y',
							'GROUP_ACTIVE' => 'Y',
							'!GROUP_ID'    => array_keys($myGroups)
						),
						false,
						false,
						array(
							'GROUP_ID',
							'GROUP_NAME'
						)
					);
					while ($row = $res->fetch())
					{
						if ($this->canReadGroupTasks($row['GROUP_ID']))
						{
							$myGroups[$row['GROUP_ID']] = $row['GROUP_NAME'];
						}
						if (count($myGroups) >= $limit)
						{
							break;
						}
					}
				}
				$this->arResult['GROUPS'] = array();

				$groupsInfo = $this->getGroupsInfo($myGroups);

				foreach ($myGroups as $gId => $gName)
				{
					$this->arResult['GROUPS'][$gId] = array(
						'id'   => $gId,
						'text' => truncateText($gName, 50),
						'image' => $groupsInfo[$gId]['IMAGE_ID'] ? $this->getAvatarSrc($groupsInfo[$gId]['IMAGE_ID']) : ''
					);
				}
			}
			// redirect, if select the group
			if ($selectGroup !== null)
			{
				$selectGroup = intval($selectGroup);
				if ($selectGroup > 0)
				{
					$path = \Bitrix\Main\Config\Option::get('tasks', 'paths_task_group', null, SITE_ID);
					$path = str_replace('#group_id#', $selectGroup, $path);
				}
				else
				{
					$path = str_replace(
						'#user_id#',
						$this->arResult['USER_ID'],
						$this->arParams['PATH_TO_USER_TASKS']
					);
				}

				\LocalRedirect($path);
			}
		}

		return parent::doPreAction();
	}

	private function getAvatarSrc($imageId): string
	{
		$arFile = \CFile::GetFileArray($imageId);
		if (is_array($arFile) && array_key_exists('SRC', $arFile))
		{
			return $arFile['SRC'];
		}
		return '';
	}

	private function getGroupsInfo($groups): array
	{
		$groupIds = array_keys($groups);
		if (empty($groupIds))
		{
			return [];
		}

		$res = WorkgroupTable::getList([
			'filter' => [
				'@ID' => $groupIds
			],
			'select' => ['ID', 'IMAGE_ID']
		]);

		$info = [];
		while($row = $res->fetch())
		{
			$info[$row['ID']] = $row;
		}

		return $info;
	}

	/**
	 * Check access to group tasks for current user.
	 *
	 * @param int $groupId Id of group.
	 *
	 * @return boolean
	 */
	protected function canReadGroupTasks($groupId)
	{
		$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $groupId);
		if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
		{
			return false;
		}
		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
			SONET_ENTITY_GROUP,
			array($groupId),
			'tasks',
			'view_all'
		);
		$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		if (!$bCanViewGroup)
		{
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				array($groupId),
				'tasks',
				'view'
			);
			$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		}
		if (!$bCanViewGroup)
		{
			return false;
		}

		return true;
	}
}