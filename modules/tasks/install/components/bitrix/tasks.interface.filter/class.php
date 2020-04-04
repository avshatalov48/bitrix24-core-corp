<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksInterfaceFilterComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
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
		static::tryParseIntegerParameter($this->arParams['GROUP_SELECTOR_LIMIT'], 5);
		static::tryParseIntegerParameter($this->arParams['GROUP_ID'], 0);
		static::tryParseArrayParameter($this->arParams['POPUP_MENU_ITEMS'], array());

		static::tryParseArrayParameter(
			$this->arParams['SHOW_CREATE_TASK_BUTTON'],
			$this->canCreateGroupTasks(
				isset($this->arParams['MENU_GROUP_ID'])
					? $this->arParams['MENU_GROUP_ID']
					: $this->arParams['GROUP_ID'])
				? 'Y' : 'N'
		);
		
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

		if ($this->arParams['USE_GROUP_SELECTOR'] == 'Y')
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
				foreach ($myGroups as $gId => $gName)
				{
					$this->arResult['GROUPS'][$gId] = array(
						'id'   => $gId,
						'text' => truncateText($gName, 50)
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