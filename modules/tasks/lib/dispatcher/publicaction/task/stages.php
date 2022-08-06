<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use \Bitrix\Tasks\Util;
use \Bitrix\Tasks\Kanban\StagesTable;
use \Bitrix\Tasks\Kanban\TaskStageTable;
use \Bitrix\Tasks\Internals\Task\SortingTable;
use \Bitrix\Tasks\Integration\SocialNetwork;
use \Bitrix\Tasks\Integration\Bizproc;
use Bitrix\Tasks\Access\ActionDictionary;

Loc::loadMessages(__FILE__);

final class Stages extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{

	/**
	 * Add new stage. Return new stage id.
	 * @param array $fields Data array.
	 * @param boolean $isAdmin Make action as portal admin.
	 * @return int|null
	 */
	public function add($fields, $isAdmin = false)
	{
		return $this->modify($fields, $isAdmin);
	}

	/**
	 * Update stage.
	 * @param int $id Stage id.
	 * @param array $fields Data array.
	 * @param boolean $isAdmin Make action as portal admin.
	 * @return boolean|null
	 */
	public function update($id, $fields, $isAdmin = false)
	{
		$id = (int)$id;
		// check
		if (!($stage = StagesTable::getById($id)->fetch()))
		{
			$this->errors->add(
				'NOT_FOUND',
				Loc::getMessage('STAGES_ERROR_NOT_FOUND')
			);
		}
		// update
		if ($this->errors->checkNoFatals())
		{
			$update = array();
			if (isset($fields['TITLE']))
			{
				$update['TITLE'] = $fields['TITLE'];
			}
			else
			{
				$update['TITLE'] = $stage['TITLE'];
			}
			if (isset($fields['COLOR']))
			{
				$update['COLOR'] = $fields['COLOR'];
			}
			if (isset($fields['AFTER_ID']))
			{
				$update['AFTER_ID'] = $fields['AFTER_ID'];
			}
			if (!empty($update))
			{
				$update['ID'] = $id;
				$update['ENTITY_ID'] = $stage['ENTITY_ID'];
				StagesTable::setWorkMode($stage['ENTITY_TYPE']);
				if ($this->modify($update, $isAdmin))
				{
					return true;
				}
			}
			else
			{
				$this->errors->add(
					'EMPTY_DATA',
					Loc::getMessage('STAGES_ERROR_EMPTY_DATA')
				);
			}
		}

		return null;
	}

	/**
	 * Delete stage.
	 * @param int $id Stage id.
	 * @param boolean $isAdmin Make action as portal admin.
	 * @return boolean|null
	 */
	public function delete($id, $isAdmin = false)
	{
		$id = (int)$id;
		// check
		if (!($stage = StagesTable::getById($id)->fetch()))
		{
			$this->errors->add(
				'NOT_FOUND',
				Loc::getMessage('STAGES_ERROR_NOT_FOUND')
			);
		}
		if (
			$this->errors->checkNoFatals() &&
			$stage['SYSTEM_TYPE'] != ''
		)
		{
			$this->errors->add(
				'IS_SYSTEM',
				Loc::getMessage('STAGES_ERROR_IS_SYSTEM')
			);
		}
		// check access
		if ($isAdmin && !$this->isAdmin())
		{
			$isAdmin = false;
		}
		if (!$isAdmin)
		{
			if ($stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP)
			{
				$this->canEditGroupStages($stage['ENTITY_ID']);
			}
			elseif ($stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_USER)
			{
				if (Util\User::getId() != $stage['ENTITY_ID'])
				{
					$this->errors->add(
						'ACCESS_DENIED',
						Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_STAGES')
					);
				}
			}
		}
		// delete
		if ($this->errors->checkNoFatals())
		{
			StagesTable::setWorkMode($stage['ENTITY_TYPE']);
			// check for exists tasks
			if ($stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP)
			{
				list($rows, $res) = \CTaskItem::fetchList(
					Util\User::getId(),
					array(),
					array(
						'STAGE_ID' => StagesTable::getStageIdByCode(
							$id,
							$stage['ENTITY_ID']
						),
						'GROUP_ID' => $stage['ENTITY_ID'],
						'CHECK_PERMISSIONS' => 'N'
					),
					array(
						'nTopCount' => 1
					),
					array(
						'ID'
					)
				);
			}
			else
			{
				$rows = TaskStageTable::getList(array(
					'filter' => array(
						'STAGE_ID' => StagesTable::getStageIdByCode(
							$id,
							$stage['ENTITY_ID']
						),
						'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
						'STAGE.ENTITY_ID' => $stage['ENTITY_ID']
					)
				))->fetch();
			}
			if (empty($rows))
			{
				$res = StagesTable::delete($id, $stage['ENTITY_ID']);
				if ($res && $res->isSuccess())
				{
					return true;
				}
			}
			else
			{
				$this->errors->add(
					'NO_EMPTY',
					Loc::getMessage('STAGES_ERROR_NO_EMPTY')
				);
			}
		}

		return null;
	}

	/**
	 * Get stages from group / user.
	 * @param int $entityId Entity id.
	 * @param boolean $numeric Numeric array.
	 * @param boolean $isAdmin Make action as portal admin.
	 * @return array
	 */
	public function get($entityId, $numeric = false, $isAdmin = false)
	{
		$entityId = (int)$entityId;
		$result = array();

		if ($isAdmin && !$this->isAdmin())
		{
			$isAdmin = false;
		}
		if ($entityId < 0)
		{
			$entityId = 0;
		}
		if (!$isAdmin && $entityId > 0)
		{
			$this->canReadGroupTask($entityId);
		}

		if ($this->errors->checkNoFatals())
		{
			if ($entityId == 0)
			{
				StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
				$entityId = Util\User::getId();
			}
			$result = StagesTable::getStages($entityId);
			if ($numeric)
			{
				$result = array_values($result);
			}
		}

		return $result;
	}

	/**
	 * Can move task.
	 * @param int $entityId Entity id.
	 * @param string $entityType Entity type.
	 * @return boolean
	 */
	public function canMoveTask($entityId, $entityType)
	{
		return $this->canSortTask($entityId, $entityType, true);
	}

	/**
	 * Move task to the new stage.
	 * @param int $id Task id.
	 * @param int $stageId Stage id.
	 * @param int $before Set before task id.
	 * @param int $after Set after task id.
	 * @return boolean|null
	 */
	public function moveTask($id, $stageId, $before = 0, $after = 0)
	{
		$success = null;
		$id = (int)$id;
		$stageId = (int)$stageId;
		// check stage
		if (!($stage = StagesTable::getById($stageId)->fetch()))
		{
			$this->errors->add(
				'NOT_FOUND',
				Loc::getMessage('STAGES_ERROR_NOT_FOUND')
			);
		}
		// chec access to task
		if ($this->errors->checkNoFatals())
		{
			$task = \CTasks::getList(
				array(
					//
				),
				array(
					'ID' => $id,
					'CHECK_PERMISSIONS' => 'Y'
				),
				array(
					'ID', 'GROUP_ID'
				)
			)->fetch();
			if (!$task)
			{
				$this->errors->add(
					'TASK_NOT_FOUND',
					Loc::getMessage('STAGES_ERROR_TASK_NOT_FOUND')
				);
			}
		}

		if ($this->errors->checkNoFatals() && Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($task['GROUP_ID']);
			$isScrumTask = ($group && $group->isScrumProject());
			if ($isScrumTask)
			{
				return $this->moveScrumTask($id, $task['GROUP_ID'], $stage);
			}
		}

		// chec access to sort
		if ($this->errors->checkNoFatals())
		{
			$this->canSortTask(
				$stage['ENTITY_ID'],
				$stage['ENTITY_TYPE']
			);
		}
		// check if new and old stages in different Kanbans
		if ($this->errors->checkNoFatals())
		{
			if (
				$stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP &&
				$task['GROUP_ID'] != $stage['ENTITY_ID']
			)
			{
				$this->errors->add(
					'DIFFERENT_STAGES',
					Loc::getMessage('STAGES_ERROR_DIFFERENT_STAGES')
				);
			}
		}

		// no errors - move task
		if ($this->errors->checkNoFatals())
		{
			if ($stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP)
			{
				$taskObj = new \CTasks;
				$taskObj->update($task['ID'], array(
					'STAGE_ID' => $stageId
				));
			}
			else
			{
				$resStg = TaskStageTable::getList(array(
					'filter' => array(
						'TASK_ID' => $id,
						'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
						'STAGE.ENTITY_ID' => $stage['ENTITY_ID']
					)
				));
				while ($rowStg = $resStg->fetch())
				{
					TaskStageTable::update($rowStg['ID'], array(
						'STAGE_ID' => $stageId
					));

					if ($stageId !== (int)$rowStg['STAGE_ID'])
					{
						Bizproc\Listener::onPlanTaskStageUpdate(
							$stage['ENTITY_ID'],
							$rowStg['TASK_ID'],
							$stageId
						);
					}
				}
			}
			$success = true;
		}
		// and set sorting
		$sortingGroup = $stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP
			? $task['GROUP_ID']
			: 0;
		// pin in new stage
		if ($before == 0 && $after == 0)
		{
			StagesTable::pinInTheStage($id, $stageId);
		}
		elseif ($before > 0)
		{
			SortingTable::setSorting(
				Util\User::getId(),
				$sortingGroup,
				$id,
				$before,
				true
			);
		}
		elseif ($after > 0)
		{
			SortingTable::setSorting(
				Util\User::getId(),
				$sortingGroup,
				$id,
				$after,
				false
			);
		}

		return $success;
	}

	/**
	 * Current user is admin?
	 * @return bool
	 */
	private function isAdmin()
	{
		return $GLOBALS['USER']->isAdmin() || \CTasksTools::IsPortalB24Admin();
	}

	/**
	 * Check if module socialnetwork is installed.
	 * @return boolean
	 */
	private function checkSonetInstalled()
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errors->add(
				'SOCIALNETWORK_IS_NOT_INSTALLED',
				Loc::getMessage('STAGES_ERROR_SOCIALNETWORK_IS_NOT_INSTALLED')
			);
			return false;
		}
		return true;
	}

	/**
	 * Check can read group tasks.
	 * @param int $groupId Group id.
	 * @return void
	 */
	private function canReadGroupTask($groupId)
	{
		if (
			$this->checkSonetInstalled() &&
			!SocialNetwork\Group::can($groupId, SocialNetwork\Group::ACTION_VIEW_OWN_TASKS) &&
			!SocialNetwork\Group::can($groupId, SocialNetwork\Group::ACTION_VIEW_ALL_TASKS)
		)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_GROUP_TASK')
			);
		}
	}

	/**
	 * Check can edit group stages.
	 * @param int $groupId Group id.
	 * @return void
	 */
	private function canEditGroupStages($groupId)
	{
		if ($this->checkSonetInstalled())
		{
			$right = \CSocNetUserToGroup::GetUserRole(Util\User::getId(), $groupId);
			if ($right != SONET_ROLES_OWNER && $right != SONET_ROLES_MODERATOR)
			{
				$this->errors->add(
					'ACCESS_DENIED',
					Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_STAGES')
				);
			}
		}
	}

	/**
	 * Check can sort tasks.
	 * @param int $entityId Entity id.
	 * @param string $entityType Entity type.
	 * @param boolean $checkMode Throw error or only check.
	 * @return boolean
	 */
	private function canSortTask($entityId, $entityType, $checkMode = false)
	{
		if ($entityType == StagesTable::WORK_MODE_GROUP)
		{
			if (
				$this->checkSonetInstalled() &&
				!SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_SORT_TASKS)
			)
			{
				if (!$checkMode)
				{
					$this->errors->add(
						'ACCESS_DENIED_MOVE',
						Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_MOVE')
					);
				}
				return false;
			}
		}
		elseif ($entityId != Util\User::getId())
		{
			if (!$checkMode)
			{
				$this->errors->add(
					'ACCESS_DENIED_MOVE',
					Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_MOVE')
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Add / update stage. Return stage id.
	 * @param array $fields Data array.
	 * @param boolean $isAdmin Make action as portal admin.
	 * @return int|null
	 */
	private function modify($fields, $isAdmin = false)
	{
		$result = null;

		// first check
		if (
			!isset($fields['TITLE']) ||
			trim($fields['TITLE']) == ''
		)
		{
			$this->errors->add(
				'EMPTY_TITLE',
				Loc::getMessage('STAGES_ERROR_EMPTY_TITLE')
			);
		}
		if (!isset($fields['ID']))
		{
			$fields['ID'] = 0;
		}
		if (
			!isset($fields['ENTITY_ID']) ||
			$fields['ENTITY_ID'] <= 0
		)
		{
			$fields['ENTITY_ID'] = 0;
		}
		// check access
		if ($isAdmin && !$this->isAdmin())
		{
			$isAdmin = false;
		}
		if (!$isAdmin && $fields['ENTITY_ID'] > 0)
		{
			if (StagesTable::getWorkMode() == StagesTable::WORK_MODE_GROUP)
			{
				$this->canEditGroupStages($fields['ENTITY_ID']);
			}
			elseif ($fields['ENTITY_ID'] != Util\User::getId())
			{
				$this->errors->add(
					'ACCESS_DENIED',
					Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_STAGES')
				);
			}
		}
		// add / update if no errors
		if ($this->errors->checkNoFatals())
		{
			$add = array(
				'TITLE' => $fields['TITLE'],
				'ENTITY_ID' => $fields['ENTITY_ID']
			);
			if (isset($fields['COLOR']))
			{
				$add['COLOR'] = $fields['COLOR'];
			}
			if (isset($fields['AFTER_ID']))
			{
				$add['AFTER_ID'] = $fields['AFTER_ID'];
			}
			if ($add['ENTITY_ID'] == 0)
			{
				StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
				$add['ENTITY_ID'] = Util\User::getId();
			}
			StagesTable::getStages($add['ENTITY_ID']);
			$res = StagesTable::updateByCode($fields['ID'], $add);
			if ($res->isSuccess())
			{
				$result = $res->getId();
			}
		}

		return $result;
	}

	private function moveScrumTask(int $taskId, int $groupId, array $stage): bool
	{
		$itemService = new ItemService();
		$entityService = new EntityService();

		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return false;
		}

		$entity = $entityService->getEntityById($scrumItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return false;
		}

		if ($entity->getEntityType() === EntityForm::BACKLOG_TYPE)
		{
			return false;
		}

		$featurePerms = \CSocNetFeaturesPerms::currentUserCanPerformOperation(
			SONET_ENTITY_GROUP,
			[$groupId],
			'tasks',
			'sort'
		);
		$isAccess = (is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId]);
		if (!$isAccess)
		{
			$this->errors->add(
				'ACCESS_DENIED_MOVE',
				Loc::getMessage('STAGES_ERROR_ACCESS_DENIED_MOVE')
			);

			return false;
		}

		$taskObject = new \CTasks;

		$queryObject = TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $taskId,
				'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'STAGE.ENTITY_ID' => $entity->getId()
			]
		]);
		if ($taskStage = $queryObject->fetch())
		{
			TaskStageTable::update($taskStage['ID'], [
				'STAGE_ID' => $stage['ID'],
			]);

			$taskObject->update($taskId, ['STAGE_ID' => $stage['ID']]);
		}

		// todo maybe need add push here

		if ($stage['SYSTEM_TYPE'] === StagesTable::SYS_TYPE_FINISH)
		{
			$this->completeTask($taskId);
		}
		else
		{
			$this->renewTask($taskId);
		}

		return true;
	}

	private function completeTask(int $taskId)
	{
		$task = \CTaskItem::getInstance($taskId, Util\User::getId());
		if (
			$task->checkAccess(ActionDictionary::ACTION_TASK_COMPLETE)
			|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
		)
		{
			$task->complete();
		}
	}

	private function renewTask(int $taskId)
	{
		$task = \CTaskItem::getInstance($taskId, Util\User::getId());
		if (
			$task->checkAccess(ActionDictionary::ACTION_TASK_RENEW)
			|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
		)
		{
			$queryObject = \CTasks::getList(
				[],
				['ID' => $taskId, '=STATUS' => \CTasks::STATE_COMPLETED],
				['ID'],
				['USER_ID' => Util\User::getId()]
			);
			if ($queryObject->fetch())
			{
				$task->renew();
			}
		}
	}
}
