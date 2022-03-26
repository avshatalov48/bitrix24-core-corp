<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

Loc::loadMessages(__FILE__);

class TasksAutomationComponent extends \CBitrixComponent
{
	protected $entity;

	protected function getProjectId()
	{
		return isset($this->arParams['PROJECT_ID']) ? (int)$this->arParams['PROJECT_ID'] : null;
	}

	protected function getTaskId()
	{
		return isset($this->arParams['TASK_ID']) ? (int)$this->arParams['TASK_ID'] : 0;
	}

	protected function getViewType()
	{
		return isset($this->arParams['VIEW_TYPE']) ? (string) $this->arParams['VIEW_TYPE'] : null;
	}

	protected function getUserId()
	{
		global $USER;
		return $USER ? (int)$USER->getId() : 0;
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('tasks'))
		{
			ShowError(Loc::getMessage('TASKS_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!Tasks\Integration\Bizproc\Automation\Factory::canUseAutomation())
		{
			ShowError(Loc::getMessage('TASKS_AUTOMATION_NOT_AVAILABLE'));
			return;
		}

		if (!Tasks\Access\TaskAccessController::can($this->getUserId(), Tasks\Access\ActionDictionary::ACTION_TASK_ROBOT_EDIT, $this->getTaskId()))
		{
			ShowError(Loc::getMessage('TASKS_AUTOMATION_NOT_AVAILABLE'));
			return;
		}

		if (TaskLimit::isLimitExceeded())
		{
			ShowError(Loc::getMessage('TASKS_AUTOMATION_NOT_AVAILABLE'));
			return;
		}

		$projectId = $this->getProjectId();
		$taskId = $this->getTaskId();
		$entityCaption = '';

		if ($taskId > 0)
		{
			$entityCaption = Tasks\Integration\Bizproc\Document\Task::getDocumentName($taskId);
		}

		$viewType = $this->getViewType();
		if (!$viewType)
		{
			$viewType = ($projectId > 0) ? 'project' : 'plan';
		}

		if ($viewType === 'project')
		{
			$documentType = Tasks\Integration\Bizproc\Document\Task::resolveProjectTaskType($projectId);

			if (Loader::includeModule('socialnetwork'))
			{
				$group = Workgroup::getById($projectId);
				if ($group && $group->isScrumProject())
				{
					$documentType = Tasks\Integration\Bizproc\Document\Task::resolveScrumProjectTaskType($projectId);
				}
			}

		}
		elseif ($viewType === 'plan')
		{
			$documentType = Tasks\Integration\Bizproc\Document\Task::resolvePlanTaskType($this->getUserId());
		}
		else
		{
			$documentType = Tasks\Integration\Bizproc\Document\Task::resolvePersonalTaskType($this->getUserId());
		}

		$this->arResult = array(
			'TASK_ID' => $taskId,
			'DOCUMENT_TYPE' => $documentType,
			'TASK_CAPTION' => $entityCaption,
			'PROJECT_ID' => $projectId,
			'VIEW_TYPE' => $viewType
		);
		$this->prepareGroupSelector($projectId);

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			global $APPLICATION;
			$APPLICATION->SetTitle(Loc::getMessage('TASKS_AUTOMATION_TITLE'));
		}

		$this->includeComponentTemplate();
	}

	private function prepareGroupSelector($groupId)
	{
		global $USER;

		$userId = $USER->getId();
		$caption = Loc::getMessage('TASKS_AUTOMATION_GROUPS_CAPTION');
		$myGroups = [];
		$limit = 4;

		if (Loader::includeModule('socialnetwork'))
		{
			if ($groupId > 0)
			{
				$res = \Bitrix\Socialnetwork\WorkgroupTable::getById($groupId);
				if ($row = $res->fetch())
				{
					$myGroups[$row['ID']] = $row['NAME'];
				}
			}

			// show last viewed groups
			$res = \Bitrix\Socialnetwork\WorkgroupViewTable::getList(
				array(
					'select' => array(
						'GROUP_ID',
						'GROUP_NAME' => 'GROUP.NAME'
					),
					'filter' => array(
						'=USER_ID'       => $userId,
						'=GROUP.ACTIVE' => 'Y',
						'=GROUP.CLOSED' => 'N'
					),
					'order'  => array(
						'DATE_VIEW' => 'DESC'
					),
					'limit' => $limit
				)
			);
			while ($row = $res->fetch())
			{
				if (isset($myGroups[$row['GROUP_ID']]))
				{
					continue;
				}

				if ($this->canReadGroupTasks($row['GROUP_ID']))
				{
					$myGroups[$row['GROUP_ID']] = $row['GROUP_NAME'];
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
						'USER_ID'      => $userId,
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
		}

		if (isset($myGroups[$groupId]))
		{
			$caption = $myGroups[$groupId];
		}

		foreach ($myGroups as $gId => $gName)
		{
			$myGroups[$gId] = array(
				'id'   => $gId,
				'text' => truncateText($gName, 50)
			);
		}

		$this->arResult['GROUPS_SELECTOR'] = array(
			'HINT' => Loc::getMessage('TASKS_AUTOMATION_GROUPS_CAPTION'),
			'CAPTION' => $caption,
			'GROUPS' => array_values($myGroups)
		);
	}

	private function canReadGroupTasks($groupId)
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

		return (bool)$bCanViewGroup;
	}
}