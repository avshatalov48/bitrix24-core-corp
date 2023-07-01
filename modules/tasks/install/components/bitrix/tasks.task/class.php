<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Disk\Driver;
use Bitrix\Main;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListConverterHelper;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListConverterHelper;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Integration\Forum\Task\Topic;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Socialnetwork\Helper\ServiceComment;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Component\Task\TasksTaskHitStateStructure;
use Bitrix\Tasks\Component\Task\TasksTaskFormState;
use Bitrix\Main\Engine\CurrentUser;

Loc::loadMessages(__FILE__);

require_once(__DIR__.'/class/taskstaskformstate.php');
require_once(__DIR__.'/class/taskstaskhitstatestructure.php');

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskComponent extends TasksBaseComponent implements Errorable, Controllerable
{
	const DATA_SOURCE_TEMPLATE = 	'TEMPLATE';
	const DATA_SOURCE_TASK = 		'TASK';

	protected $task = 			null;
	protected $users2Get = 		array();
	protected $groups2Get = 	array();
	protected $tasks2Get = 		array();
	protected $formData = 		false;

	private $success =          false;
	private $responsibles = 	false;
	private $eventType =        false;
	private $eventTaskId =      false;
	private $eventOptions =     array();

	protected $hitState =          null;

	protected $errorCollection;

	public function configureActions()
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'checkCanRead' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setMark' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setGroup' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setReminder' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setDeadline' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'addElapsedTime' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'updateElapsedTime' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'deleteElapsedTime' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'get' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'start' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'complete' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'delegate' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'defer' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'delete' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'renew' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'pause' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'addFavorite' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'deleteFavorite' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setPriority' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getFileCount' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getFiles' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'mute' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'unmute' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'ping' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'approve' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'disapprove' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setTags' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'legacyUpdate' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'legacyAdd' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'startTimer' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'stopTimer' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'canMoveStage' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getStages' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'moveStage' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setState' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'uiEdit' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function uiEditAction($taskId = 0, array $parameters = [])
	{
		global $APPLICATION;

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$taskId = (int)$taskId;

		if ($taskId && !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$componentParameters = array();
		if(is_array($parameters['COMPONENT_PARAMETERS']))
		{
			$componentParameters = $parameters['COMPONENT_PARAMETERS'];
		}
		$componentParameters = array_merge(array_intersect_key($componentParameters, array_flip(array(
			// component parameter white-list place here
			'GROUP_ID',
			'PATH_TO_USER_TASKS',
			'PATH_TO_USER_TASKS_TASK',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_GROUP_TASKS_TASK',
			'PATH_TO_USER_PROFILE',
			'PATH_TO_GROUP',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW',
			'PATH_TO_USER_TASKS_TEMPLATES',
			'PATH_TO_USER_TEMPLATES_TEMPLATE',
			'ENABLE_FOOTER',
			'ENABLE_FORM',

			'TEMPLATE_CONTROLLER_ID',
			'BACKURL',
		))), array(
			// component force-to parameters place here
			'ID' => $taskId,
			'SET_NAVCHAIN' => 'N',
			'SET_TITLE' => 'N',
			'SUB_ENTITY_SELECT' => array(
				'TAG',
				'CHECKLIST',
				'REMINDER',
				'PROJECTDEPENDENCE',
				'TEMPLATE',
				'RELATEDTASK'
			),
			'AUX_DATA_SELECT' => array(
				'COMPANY_WORKTIME',
				'USER_FIELDS',
			),
			'ENABLE_FOOTER_UNPIN' => 'N',
			'ENABLE_MENU_TOOLBAR' => 'N',
			//'REDIRECT_ON_SUCCESS' => 'N',
			'CANCEL_ACTION_IS_EVENT' => true,
		));

		$componentParameters["ACTION"] = "edit";

		return new Component('bitrix:tasks.task', '', $componentParameters);
	}

	public function setStateAction(array $state = [])
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		TasksTaskFormState::set($state);
	}

	public function moveStageAction($taskId, $stageId, $before = 0, $after = 0)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		$stageId = (int) $stageId;
		if (!$stageId)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return null;
		}

		$result = [];

		// check stage
		if (!($stage = StagesTable::getById($stageId)->fetch()))
		{
			$this->addForbiddenError();
			return $result;
		}

		// check access to task
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = TaskRegistry::getInstance()->get($taskId);
		if (!$task)
		{
			$this->addForbiddenError();
			return $result;
		}

		$group = \Bitrix\Socialnetwork\Item\Workgroup::getById($task['GROUP_ID']);
		$isScrumTask = ($group && $group->isScrumProject());
		if ($isScrumTask)
		{
			$featurePerms = \CSocNetFeaturesPerms::currentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$task['GROUP_ID']],
				'tasks',
				'sort'
			);
			$isAccess = (
				is_array($featurePerms)
				&& isset($featurePerms[$task['GROUP_ID']])
				&& $featurePerms[$task['GROUP_ID']]
			);
			if (!$isAccess)
			{
				return false;
			}

			$kanbanService = new KanbanService();

			$result = $kanbanService->moveTask($taskId, $stage['ID']);

			if ($stage['SYSTEM_TYPE'] === StagesTable::SYS_TYPE_FINISH)
			{
				$this->completeTask($taskId);
			}
			else
			{
				$this->renewTask($taskId);
			}

			return $result;
		}

		if (
			$stage['ENTITY_TYPE'] === StagesTable::WORK_MODE_GROUP
			&& !SocialNetwork\Group::can($stage['ENTITY_ID'], SocialNetwork\Group::ACTION_SORT_TASKS)
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		if (
			$stage['ENTITY_TYPE'] !== StagesTable::WORK_MODE_GROUP
			&& ((int)$stage['ENTITY_ID']) !== $this->userId
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		// check if new and old stages in different Kanbans
		if (
			$stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP
			&& $task['GROUP_ID'] != $stage['ENTITY_ID']
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		// no errors - move task
		if ($stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP)
		{
			$taskObj = new \CTasks;
			$taskObj->update($task['ID'], array(
				'STAGE_ID' => $stageId
			));
		}
		else
		{
			$resStg = Tasks\Kanban\TaskStageTable::getList(array(
				'filter' => array(
					'TASK_ID' => $taskId,
					'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
					'STAGE.ENTITY_ID' => $stage['ENTITY_ID']
				)
			));
			while ($rowStg = $resStg->fetch())
			{
				Tasks\Kanban\TaskStageTable::update($rowStg['ID'], array(
					'STAGE_ID' => $stageId
				));

				if ($stageId !== (int)$rowStg['STAGE_ID'])
				{
					Integration\Bizproc\Listener::onPlanTaskStageUpdate(
						$stage['ENTITY_ID'],
						$rowStg['TASK_ID'],
						$stageId
					);
				}
			}
		}

		// and set sorting
		$sortingGroup = $stage['ENTITY_TYPE'] == StagesTable::WORK_MODE_GROUP
			? $task['GROUP_ID']
			: 0;
		// pin in new stage
		if ($before == 0 && $after == 0)
		{
			StagesTable::pinInTheStage($taskId, $stageId);
		}
		elseif ($before > 0)
		{
			Tasks\Internals\Task\SortingTable::setSorting(
				Util\User::getId(),
				$sortingGroup,
				$taskId,
				$before,
				true
			);
		}
		elseif ($after > 0)
		{
			Tasks\Internals\Task\SortingTable::setSorting(
				Util\User::getId(),
				$sortingGroup,
				$taskId,
				$after,
				false
			);
		}

		return true;
	}

	public function canMoveStageAction($entityId, $entityType)
	{
		$entityId = (int) $entityId;
		if (!$entityId)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return null;
		}

		if (
			$entityType === StagesTable::WORK_MODE_GROUP
			&& !SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_SORT_TASKS)
		)
		{
			return false;
		}

		if (
			$entityType !== StagesTable::WORK_MODE_GROUP
			&& $entityId !== $this->userId
		)
		{
			return false;
		}

		return true;
	}

	public function needRestrictResponsibleAction(int $groupId): ?bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		if ($groupId <= 0)
		{
			return true;
		}

		$canEditTasks = FeaturePermRegistry::getInstance()->get(
			$groupId,
			'tasks',
			'edit_tasks',
			$this->userId
		);

		if ($canEditTasks)
		{
			return false;
		}

		return true;
	}

	public function getStagesAction($entityId, $isNumeric = false)
	{
		$entityId = (int) $entityId;
		if ($entityId < 0)
		{
			$entityId = 0;
		}

		$isNumeric = (bool)$isNumeric;

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return null;
		}

		$result = [];

		if (
			$entityId > 0
			&& !SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_VIEW_OWN_TASKS)
			&& !SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_VIEW_ALL_TASKS)
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($entityId == 0)
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
			$entityId = $this->userId;
		}

		$result = StagesTable::getStages($entityId);
		if ($isNumeric)
		{
			$result = array_values($result);
		}

		return $result;
	}

	public function startTimerAction($taskId, $stopPrevious = false)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_TIME_TRACKING, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$timer = \CTaskTimerManager::getInstance($this->userId);
		$lastTimer = $timer->getLastTimer();
		if (
			!$stopPrevious
			&& $lastTimer['TASK_ID']
			&& $lastTimer['TIMER_STARTED_AT'] > 0
			&& intval($lastTimer['TASK_ID'])
			&& $lastTimer['TASK_ID'] != $taskId
		)
		{
			$additional = [];

			// use direct query here, avoiding cached CTaskItem::getData(), because $lastTimer['TASK_ID'] unlikely will be in cache
			[$tasks, $res] = \CTaskItem::fetchList($this->userId, [], ['ID' => (int)$lastTimer['TASK_ID']], [], ['ID', 'TITLE']);
			if(is_array($tasks))
			{
				$task = array_shift($tasks);
				if($task)
				{
					$data = $task->getData(false);
					if(intval($data['ID']))
					{
						$additional['TASK'] = array(
							'id' => $data['ID'],
							'title' => $data['TITLE']
						);
					}
				}
			}

			$this->errorCollection->add('ACTION_FAILED.OTHER_TASK_ON_TIMER', Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), false, $additional);
		}
		else
		{
			if($timer->start($taskId) === false)
			{
				$this->errorCollection->add('ACTION_FAILED', Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'));
			}
		}

		return $result;
	}

	public function stopTimerAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_TIME_TRACKING, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$timer = \CTaskTimerManager::getInstance(User::getId());
		if($timer->stop($taskId) === false)
		{
			$this->errorCollection->add('ACTION_FAILED', Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'));
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @param array $data
	 * @param array $parameters
	 * @throws Main\LoaderException
	 *
	 * @deprecated since tasks 22.400.0
	 */
	public function legacyUpdateAction($taskId, array $data, array $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$oldTask = TaskModel::createFromId($taskId);
		$newTask = clone $oldTask;

		if (
			isset($parameters['PLATFORM'])
			&& $parameters['PLATFORM'] === 'mobile'
		)
		{
			$data = $this->prepareMobileData($data, $taskId);
		}

		if (
			count($data) < 3
			&& count(array_intersect(array_keys($data), ['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'])) === count($data)
		)
		{
			$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, $taskId);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_RESPONSIBLE', $data)
		)
		{
			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_RESPONSIBLE] = [];
			if (
				!empty($data['SE_RESPONSIBLE'])
				&& is_array($data['SE_RESPONSIBLE'])
			)
			{
				foreach ($data['SE_RESPONSIBLE'] as $responsible)
				{
					$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int)$responsible['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $oldTask, $newTask);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_ACCOMPLICE', $data)
		)
		{
			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_ACCOMPLICE] = [];
			if (
				!empty($data['SE_ACCOMPLICE'])
				&& is_array($data['SE_ACCOMPLICE'])
			)
			{
				foreach ($data['SE_ACCOMPLICE'] as $accomplice)
				{
					$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int)$accomplice['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $oldTask, $newTask);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_REMINDER', $data)
		)
		{
			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_REMINDER, $oldTask, $data['SE_REMINDER']);
		}
		else
		{
			$newTask = TaskModel::createFromRequest($data);
			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if (!empty($data))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$mgrResult = Manager\Task::update($this->userId, $taskId, $data, array(
				'PUBLIC_MODE' => true,
				'ERRORS' => $this->errorCollection,
				'THROTTLE_MESSAGES' => $parameters[ 'THROTTLE_MESSAGES' ],

				// there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
				'RETURN_ENTITY' => $parameters[ 'RETURN_ENTITY' ],
			));

			$result['ID'] = $taskId;
			$result['DATA'] = $mgrResult['DATA'];
			$result['CAN'] = $mgrResult['CAN'];

			if ($this->errorCollection->checkNoFatals())
			{
				if ($parameters['RETURN_OPERATION_RESULT_DATA'])
				{
					$task = $mgrResult['TASK'];

					$lastOperation = $task->getLastOperationResultData('UPDATE');
					$shiftResult = $lastOperation['SHIFT_RESULT'] ?? [];

					$result['OPERATION_RESULT']['SHIFT_RESULT'] = [];
					foreach ($shiftResult as $shift)
					{
						$result['OPERATION_RESULT']['SHIFT_RESULT'][$shift['ID']] = $shift;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @param array $parameters
	 * @return array|null
	 * @throws Main\LoaderException
	 *
	 * @deprecated since tasks 22.400.0
	 */
	public function legacyAddAction(array $data, array $parameters = ['RETURN_DATA' => false])
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$newTask = TaskModel::createFromRequest($data);
		$oldTask = TaskModel::createNew($newTask->getGroupId());

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		// todo: move to \Bitrix\Tasks\Item\Task
		$mgrResult = Manager\Task::add($this->userId, $data, [
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errorCollection,
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY']
		]);

		return [
			'ID' => $mgrResult[ 'DATA' ][ 'ID' ],
			'DATA' => $mgrResult[ 'DATA' ],
			'CAN' => $mgrResult[ 'CAN' ],
		];
	}

	public function checkCanReadAction($taskId)
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		return [
			'READ' => TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskId)
		];
	}

	public function setTagsAction($taskId, array $tags = [], string $newTag = ''): ?array
	{
		$taskId = (int)$taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$task = TaskRegistry::getInstance()->get($taskId);

		if (is_null($task))
		{
			return null;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$groupId = 0;
		$groupName = '';

		if (!is_null($task['GROUP_INFO']))
		{
			$groupId = $task['GROUP_INFO']['ID'];
			$groupName = $task['GROUP_INFO']['NAME'];
		}

		if (!empty(trim($newTag)))
		{
			$tagService = new Bitrix\Tasks\Control\Tag($this->userId);

			if ($tagService->isExists($newTag, $groupId, $taskId))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TASKS_TASK_TAG_ALREADY_EXISTS'),
				];
			}

			$tags[] = $newTag;
		}

		$this->updateTask(
			$taskId,
			[
				'TAGS' => $tags,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return [
				'success' => true,
				'error' => '',
				'owner' => empty($groupName) ? CurrentUser::get()->getFormattedName() : $groupName,
			];
		}

		return [
			'success' => false,
			'error' => Loc::getMessage('TASKS_TASK_TAG_UNKNOWN_ERROR'),
		];
	}

	public function setGroupAction($taskId, $groupId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$oldTask = TaskModel::createFromId($taskId);
		$newTask = clone $oldTask;
		$newTask->setGroupId($groupId);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'GROUP_ID' => $groupId,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	public function isScrumProjectAction(int $groupId): ?bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$group = Bitrix\Socialnetwork\Item\Workgroup::getById($groupId);

		return ($group && $group->isScrumProject());
	}

	public function needShowEpicFieldAction(int $groupId): ?bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		if (!Group::canReadGroupTasks(\Bitrix\Tasks\Util\User::getId(), $groupId))
		{
			return false;
		}

		$group = Bitrix\Socialnetwork\Item\Workgroup::getById($groupId);

		return ($group && $group->isScrumProject());
	}

	public function approveAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_APPROVE, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->approve();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function disapproveAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DISAPPROVE, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->disapprove();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function pingAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$taskData = $task->getData(false);
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		if ($taskData)
		{
			$commentPoster = CommentPoster::getInstance($taskId, $this->userId);
			$commentPoster && $commentPoster->postCommentsOnTaskStatusPinged($taskData);

			\CTaskNotifications::sendPingStatusMessage($taskData, $this->userId);
		}

		return $result;
	}

	public function muteAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		UserOption::add($taskId, $this->userId, UserOption\Option::MUTED);
		return $result;
	}

	public function unmuteAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		UserOption::delete($taskId, $this->userId, UserOption\Option::MUTED);
		return $result;
	}

	public function getFilesAction($taskId)
	{
		global $APPLICATION;

		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
			|| !Loader::includeModule('disk')
		)
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($taskId);
		if (!$task)
		{
			return 0;
		}

		$topicId = $task->getForumTopicId();
		$forumId = Comment::getForumId();

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:disk.uf.comments.attached.objects",
			".default",
			[
				"MAIN_ENTITY" => [
					"ID" => $taskId
				],
				"COMMENTS_MODE" => "forum",
				"ENABLE_AUTO_BINDING_VIEWER" => false, // Viewer cannot work in the iframe (see logic.js)
				"DISABLE_LOCAL_EDIT" => 0,
				"COMMENTS_DATA" => [
					"TOPIC_ID" => $topicId,
					"FORUM_ID" => $forumId,
					"XML_ID" => "TASK_".$taskId
				],
				"PUBLIC_MODE" => 0
			],
			false,
			["HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y"]
		);
		$html = ob_get_contents();
		ob_end_clean();

		$assetHtml = array_values(static::getApplicationResources());

		return [
			"html" => $html,
			"asset" => $assetHtml,
		];
	}

	public function getFileCountAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
		)
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$fileCount = Topic::getFileCount($taskId);

		return ["fileCount" => $fileCount];
	}

	public function setPriorityAction($taskId, $priority)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		$priority = (int) $priority;
		if (!$priority)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'PRIORITY' => $priority,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return $result;
	}

	public function addFavoriteAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = new \CTaskItem($taskId, $this->userId);
		$task->addToFavorite();

		return $result;
	}

	public function deleteFavoriteAction($taskId)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = new \CTaskItem($taskId, $this->userId);
		$task->deleteFromFavorite();

		return $result;
	}

	public function pauseAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_PAUSE, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->pauseExecution();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function renewAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_RENEW, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->renew();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function startAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_START, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->startExecution();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function completeAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$task = TaskModel::createFromId($taskId);
		if ($task->isClosed())
		{
			return $result;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE_RESULT, $taskId))
		{
			$this->errorCollection->add('RESULT_REQUIRED', Loc::getMessage('TASKS_ACTION_RESULT_REQUIRED'), false, ['ui' => 'notification']);
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->complete();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function delegateAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (
			!array_key_exists('userId', $parameters)
			|| !(int) $parameters['userId']
		)
		{
			return null;
		}
		$userId = (int) $parameters['userId'];

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$oldTask = TaskModel::createFromId($taskId);
		$newTask = clone $oldTask;
		$members = $newTask->getMembers();
		$members[RoleDictionary::ROLE_RESPONSIBLE] = [
			$userId
		];
		$newTask->setMembers($members);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_DELEGATE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->delegate($userId);
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function deferAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEFER, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, Util\User::getId());
			$task->defer();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function deleteAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_REMOVE, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->delete();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		$result['id'] = $taskId;

		return $result;
	}

	public function getAction($taskId, $parameters = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		$mgrResult = Manager\Task::get($this->userId, $taskId, [
			'ENTITY_SELECT' => $parameters['ENTITY_SELECT'] ?? null,
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors
		]);

		if ($this->errorCollection->checkNoFatals())
		{
			$result = [
				'ID' => $taskId,
				'DATA' => $mgrResult[ 'DATA' ],
				'CAN' => $mgrResult[ 'CAN' ]
			];
		}

		return $result;
	}

	public function addElapsedTimeAction($taskId, $data)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$data['TASK_ID'] = $taskId;
		$result = Manager\Task\ElapsedTime::add($this->userId, $data, [
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => true,
		]);

		return [
			'DATA' => $result['DATA'],
			'CAN' => $result['CAN'],
		];
	}

	public function updateElapsedTimeAction($id, $data)
	{
		$id = (int) $id;
		if (!$id)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$res = Tasks\Internals\Task\ElapsedTimeTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'=ID' => $id,
			],
			'limit' => 1,
		])->fetchRaw();

		if (!$res)
		{
			$this->addForbiddenError();
			return [];
		}

		$taskId = (int) $res['TASK_ID'];

		if (
			!$taskId
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, $taskId)
		)
		{
			$this->addForbiddenError();
			return [];
		}

		$result = Manager\Task\ElapsedTime::update($this->userId, $id, $data, [
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => true,
		]);

		return [
			'DATA' => $result['DATA'],
			'CAN' => $result['CAN'],
		];
	}

	public function deleteElapsedTimeAction($taskId, $id)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		$id = (int) $id;
		if (!$id)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, $taskId);
		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, $this->userId);
			$item = new \CTaskElapsedItem($task, $id);
			$item->delete();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	public function setDeadlineAction($taskId, $date = null)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, $taskId);

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'DEADLINE' => $date,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	public function setReminderAction($taskId, $data = null)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$oldTask = TaskModel::createFromId($taskId);
		$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_REMINDER, $oldTask, $data);

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'SE_REMINDER' => $data,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	public function setMarkAction($taskId, $mark)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		$oldTask = TaskModel::createFromId($taskId);
		$newTask = TaskModel::createFromRequest(['MARK' => $mark]);

		$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask);

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'MARK' => $mark,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	protected function processExecutionStart()
	{
		$this->hitState = new TasksTaskHitStateStructure($this->request->toArray());
	}

	/**
	 * Function checks if required modules installed. Also check for available features
	 * @throws Exception
	 * @return bool
	 */
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			$errors->add('SOCIALNETWORK_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TT_SOCIALNETWORK_MODULE_NOT_INSTALLED"));
		}

		if(!Loader::includeModule('forum'))
		{
			$errors->add('FORUM_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TT_FORUM_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return bool
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);
		static::checkRestrictions($arParams, $arResult, $errors);

		if($errors->checkNoFatals())
		{
			// check task access
			$taskId = intval($arParams[static::getParameterAlias('ID')]);
			if($taskId)
			{
				$arResult['TASK_INSTANCE'] = CTaskItem::getInstanceFromPool($taskId, $arResult['USER_ID']);
			}
		}

		return $errors->checkNoFatals();
	}

	protected static function checkRights(array $arParams, array $arResult, array $auxParams): ?Util\Error
	{
		$error = new Util\Error(Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), 'ACCESS_DENIED.NO_TASK');

		$request = null;
		if (array_key_exists('REQUEST', $auxParams))
		{
			$request = $auxParams['REQUEST'];
			if ($request instanceof \Bitrix\Main\Type\ParameterDictionary)
			{
				$request = $request->toArray();
			}
		}

		$taskId = (int) $arParams[static::getParameterAlias('ID')];
		if (
			!$taskId
			&& $request
		)
		{
			if ($request['ACTION'][0]['ARGUMENTS']['taskId'] ?? null)
			{
				$taskId = (int) $request['ACTION'][0]['ARGUMENTS']['taskId'];
			}
			elseif ($request['ACTION'][0]['ARGUMENTS']['params']['TASK_ID'] ?? null)
			{
				$taskId = (int) $request['ACTION'][0]['ARGUMENTS']['params']['TASK_ID'];
			}
		}
		$groupId = (int) $arParams['GROUP_ID'];

		$oldTask = $taskId ? TaskModel::createFromId($taskId) : TaskModel::createNew($groupId);
		$newTask = $request && isset($request['ACTION'][0]['ARGUMENTS']['data'])
			? TaskModel::createFromRequest($request['ACTION'][0]['ARGUMENTS']['data'])
			: null;

		$accessCheckParams = $newTask;

		$action = Tasks\Access\ActionDictionary::ACTION_TASK_READ;

		if (
			$request
			&& isset($request['ACTION'])
			&& (is_array($request['ACTION']))
			&& ($request['ACTION'][0]['OPERATION'] ?? null) === 'task.add'
		)
		{
			$action = Tasks\Access\ActionDictionary::ACTION_TASK_SAVE;

			// Crutch.
			// Temporary stub to disable creation subtask if user has no access.
			// It's make me cry.
			if (count($newTask->getMembers(Tasks\Access\Role\RoleDictionary::ROLE_RESPONSIBLE)) <= 1)
			{
				$error->setType(Util\Error::TYPE_ERROR);
			}
			$error->setCode('ERROR_TASK_CREATE_ACCESS_DENIED');
			$error->setMessage(Loc::getMessage('TASKS_TASK_CREATE_ACCESS_DENIED'));
		}
		else if (
			$request
			&& isset($request['ACTION'])
			&& (is_array($request['ACTION']))
			&& ($request['ACTION'][0]['OPERATION'] ?? null) === 'task.update'
		)
		{
			$action = Tasks\Access\ActionDictionary::ACTION_TASK_SAVE;
		}
		else if ($arParams['ACTION'] === "edit" && $taskId)
		{
			$action = Tasks\Access\ActionDictionary::ACTION_TASK_EDIT;
		}
		else if ($arParams['ACTION'] === "edit")
		{
			$action = Tasks\Access\ActionDictionary::ACTION_TASK_CREATE;
		}

		$res = (new Tasks\Access\TaskAccessController($arResult['USER_ID']))->check($action, $oldTask, $accessCheckParams);

		if (!$res)
		{
			return $error;
		}

		return null;
	}

	protected static function checkRestrictions(array &$arParams, array &$arResult, Collection $errors)
	{
		if(!\Bitrix\Tasks\Util\Restriction::canManageTask())
		{
			$errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_RESTRICTED'));
		}
	}

	/**
	 * Function checks and prepares only the basic parameters passed
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		static::tryParseIntegerParameter($arParams[static::getParameterAlias('ID')], 0, true); // parameter keeps currently chosen task ID

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks and prepares all the parameters passed
	 * @return bool
	 */
	protected function checkParameters()
	{
		parent::checkParameters();
		if($this->arParams['USER_ID'])
		{
			$this->users2Get[] = $this->arParams['USER_ID'];
		}

		static::tryParseIntegerParameter($this->arParams['GROUP_ID'], 0);
		if($this->arParams['GROUP_ID'])
		{
			$this->groups2Get[] = $this->arParams['GROUP_ID'];
		}

		static::tryParseArrayParameter($this->arParams['SUB_ENTITY_SELECT']);
		static::tryParseArrayParameter($this->arParams['AUX_DATA_SELECT']);

		static::tryParseBooleanParameter($this->arParams['REDIRECT_ON_SUCCESS'], true);
		static::tryParseURIParameter($this->arParams['BACKURL']);

		static::tryParseStringParameter($this->arParams['PLATFORM'], 'web');

		return $this->errors->checkNoFatals();
	}

	/**
	 * Allows to decide which data should be passed to $this->arResult, and which should not
	 */
	protected function translateArResult($arResult)
	{
		if(isset($arResult['TASK_INSTANCE']) && $arResult['TASK_INSTANCE'] instanceof CTaskItem)
		{
			$this->task = $arResult['TASK_INSTANCE']; // a short-cut to the currently selected task instance
			unset($arResult['TASK_INSTANCE']);
		}

		parent::translateArResult($arResult); // all other will merge to $this->arResult
	}

	protected function processBeforeAction($trigger = array())
	{
		$request = static::getRequest()->toArray();

		if (Type::isIterable($request['ADDITIONAL'] ?? null))
		{
			$this->setDataSource(
				($request['ADDITIONAL']['DATA_SOURCE']['TYPE'] ?? null),
				($request['ADDITIONAL']['DATA_SOURCE']['ID'] ?? null)
			);
		}

		// set responsible id and multiple
		if(Type::isIterable($trigger) && Type::isIterable($trigger[0]))
		{
			$action =& $trigger[0];
			$taskData =& $action['ARGUMENTS']['data'];

			if(Type::isIterable($taskData))
			{
				$this->setResponsibles($this->extractResponsibles($taskData));
			}

			$responsibles = $this->getResponsibles();

			// invite all members...
			static::inviteUsers($responsibles, $this->errors);
			if(array_key_exists('SE_AUDITOR', $taskData))
			{
				static::inviteUsers($taskData['SE_AUDITOR'], $this->errors);
			}
			if(array_key_exists('SE_ACCOMPLICE', $taskData))
			{
				static::inviteUsers($taskData['SE_ACCOMPLICE'], $this->errors);
			}

			$this->setResponsibles($responsibles);

			if(!empty($responsibles))
			{
				$taskData =& $action['ARGUMENTS']['data'];

				// create here...

				if($action['OPERATION'] == 'task.add')
				{
					// a bit more interesting
					if(count($responsibles) > 1)
					{
						$taskData['MULTITASK'] = 'Y';

						// this "root" task will have current user as responsible
						// RESPONSIBLE_ID has higher priority than SE_RESPONSIBLE, so its okay
						$taskData['RESPONSIBLE_ID'] = $this->userId;
					}
				}
			}
		}

		return $trigger;
	}

	private function getTaskActionResult()
	{
		return $this->arResult['ACTION_RESULT']['task_action'];
	}

	protected function processAfterAction()
	{
		$actionResult = $this->getTaskActionResult();

		if (empty($actionResult) || !Type::isIterable($actionResult))
		{
			return;
		}

		if ($actionResult['SUCCESS'])
		{
			$actionTaskId = static::getOperationTaskId($actionResult);

			$this->processAfterSaveAction($actionResult);

			$this->setEventType(($actionResult['OPERATION'] === 'task.add' ? 'ADD' : 'UPDATE'));
			$this->setEventTaskId($actionTaskId);
			$this->setEventOption('STAY_AT_PAGE', (bool)$this->request['STAY_AT_PAGE']);
			$this->setEventOption('SCOPE', $this->request['SCOPE']);
			$this->setEventOption(
				'FIRST_GRID_TASK_CREATION_TOUR_GUIDE',
				($this->request['FIRST_GRID_TASK_CREATION_TOUR_GUIDE'] === 'Y')
			);

			foreach ($actionResult['ERRORS'] as $error)
			{
				if ($error['CODE'] === 'SAVE_AS_TEMPLATE_ERROR')
				{
					$this->errors->addWarning(
						$error['CODE'],
						Loc::getMessage('TASKS_TT_SAVE_AS_TEMPLATE_ERROR_MESSAGE_PREFIX') . ': ' . $error['MESSAGE']
					);
				}
			}

			if (!$this->errors->find(['CODE' => 'SAVE_AS_TEMPLATE_ERROR'])->isEmpty())
			{
				$this->setEventOption('STAY_AT_PAGE', true);
			}
			elseif ($this->arParams['REDIRECT_ON_SUCCESS'])
			{
				LocalRedirect($this->makeRedirectUrl($actionResult));
			}

			$this->formData = false;
			$this->success = true;
		}
		else
		{
			$actionTaskId = false;

			// merge errors
			if (!empty($actionResult['ERRORS']))
			{
				$errorCreate = false;
				foreach ($this->errors as $error)
				{
					if ($error->getCode() === 'ERROR_TASK_CREATE_ACCESS_DENIED')
					{
						$errorCreate = true;
					}
				}
				if ($errorCreate)
				{
					foreach ($actionResult['ERRORS'] as $k => $error)
					{
						if ($error['CODE'] === 'ACTION_NOT_ALLOWED.RESTRICTED')
						{
							unset($actionResult['ERRORS'][$k]);
						}
					}
				}
				$this->errors->addForeignErrors($actionResult['ERRORS'], ['CHANGE_TYPE_TO' => Util\Error::TYPE_ERROR]);
			}
			$this->formData = Task::normalizeData($actionResult['ARGUMENTS']['data']);
			$this->success = false;
		}

		$this->arResult['COMPONENT_DATA']['ACTION'] = [
			'SUCCESS' => $this->success,
			'ID' => $actionTaskId,
		];
	}

	protected function processAfterSaveAction(array $actionResult)
	{
		$this->manageSubTasks();
		$this->manageTemplates();
		$this->handleSourceEntity();
		$this->handleFirstGridTaskCreationTourGuide();
	}

	private function manageSubTasks()
	{
		Tasks\Item\Task::enterBatchState();

		$operationResult = $this->getTaskActionResult();
		$operation = $operationResult['OPERATION'];

		if($operation == 'task.add')
		{
			$mainTaskId = static::getOperationTaskId($operationResult);
			$this->createSubTasks($mainTaskId);
		}

		Tasks\Item\Task::leaveBatchState();
	}

	private function manageTemplates()
	{
		Tasks\Item\Task\Template::enterBatchState();

		$operationResult = $this->getTaskActionResult();
		$operation = $operationResult['OPERATION'];

		$isAdd = $operation == 'task.add';
		$isUpdate = $operation == 'task.update';

		if($isAdd || $isUpdate) // in add or update
		{
			// todo: probably, when $isUpdate, try to update an existing template
			// todo: also, delete existing template

			$mainTaskId = static::getOperationTaskId($operationResult);
			$this->createTemplate($mainTaskId);
		}
		// todo: move logic from \Bitrix\Tasks\Manager\Task\Template::manageTaskReplication() here

		Tasks\Item\Task\Template::leaveBatchState();
	}

	private function handleSourceEntity(): void
	{
		$operationResult = $this->getTaskActionResult();
		$isTaskAdding = ($operationResult['OPERATION'] === 'task.add');

		if ($isTaskAdding)
		{
			if ((int)$this->request->get('CALENDAR_EVENT_ID') > 0)
			{
				$this->handleCalendarEvent();
			}
			elseif (
				!empty((string)$this->request->get('SOURCE_ENTITY_TYPE'))
				&& (int)$this->request->get('SOURCE_ENTITY_ID') > 0
			)
			{
				$operationResult = $this->getTaskActionResult();

				if (Loader::includeModule('socialnetwork'))
				{
					ServiceComment::processLogEntryCreateEntity([
						'ENTITY_TYPE' => 'TASK',
						'ENTITY_ID' => $operationResult['RESULT']['ID'],
						'POST_ENTITY_TYPE' => (string)$this->request->get('SOURCE_POST_ENTITY_TYPE'),
						'SOURCE_ENTITY_TYPE' => (string)$this->request->get('SOURCE_ENTITY_TYPE'),
						'SOURCE_ENTITY_ID' => (int)$this->request->get('SOURCE_ENTITY_ID'),
//						'SOURCE_ENTITY_DATA' => $calendarEventData,
						'LIVE' => 'Y'
					]);

				}
			}
		}
	}


	private function handleCalendarEvent(): void
	{
		$operationResult = $this->getTaskActionResult();

		if ($calendarEventId = (int)$this->request->get('CALENDAR_EVENT_ID'))
		{
			$calendarEventData = $this->request->get('CALENDAR_EVENT_DATA');
			try
			{
				$calendarEventData = \Bitrix\Main\Web\Json::decode($calendarEventData);
			}
			catch (\Bitrix\Main\SystemException $e)
			{
				$calendarEventData = [];
			}

			// post comment to calendar event
			if (Loader::includeModule('socialnetwork'))
			{
				\Bitrix\Socialnetwork\Helper\ServiceComment::processLogEntryCreateEntity([
					'ENTITY_TYPE' => 'TASK',
					'ENTITY_ID' => $operationResult['RESULT']['ID'],
					'POST_ENTITY_TYPE' => 'CALENDAR_EVENT',
					'SOURCE_ENTITY_TYPE' => 'CALENDAR_EVENT',
					'SOURCE_ENTITY_ID' => $calendarEventId,
					'SOURCE_ENTITY_DATA' => $calendarEventData,
					'LIVE' => 'Y'
				]);
			}
		}
	}

	private function handleFirstGridTaskCreationTourGuide(): void
	{
		$operationResult = $this->getTaskActionResult();
		$isTaskAdding = ($operationResult['OPERATION'] === 'task.add');

		if ($isTaskAdding && $this->request->get('FIRST_GRID_TASK_CREATION_TOUR_GUIDE') === 'Y')
		{
			$firstGridTaskCreationTour = Bitrix\Tasks\TourGuide\FirstGridTaskCreation::getInstance($this->userId);
			$firstGridTaskCreationTour->finish();
		}
	}

	private static function getOperationTaskId(array $operation)
	{
		return (int)($operation['RESULT']['DATA']['ID'] ?? null); // task.add and task.update always return TASK_ID on success
	}

	private function makeRedirectUrl(array $operation)
	{
		$isActionAdd = ($operation['OPERATION'] === 'task.add');
		$resultTaskId = static::getOperationTaskId($operation);

		$backUrl = $this->getBackUrl();
		$url = ($backUrl != '' ? Util::secureBackUrl($backUrl) : $GLOBALS['APPLICATION']->GetCurPageParam(''));

		$action = 'view'; // having default backurl after success edit we go to view ...

		// .. but there are some exceptions
		$taskId = 0;
		if ($isActionAdd)
		{
			$taskId = $resultTaskId;
			if ($this->request['STAY_AT_PAGE'])
			{
				$taskId = 0;
				$action = 'edit';
			}
		}

		$url = UI\Task::makeActionUrl($url, $taskId, $action);
		$url = UI\Task::cleanFireEventUrl($url);
		$url = UI\Task::makeFireEventUrl(
			$url,
			$this->getEventTaskId(),
			$this->getEventType(),
			[
				'STAY_AT_PAGE' => $this->getEventOption('STAY_AT_PAGE'),
				'SCOPE' => $this->getEventOption('SCOPE'),
				'FIRST_GRID_TASK_CREATION_TOUR_GUIDE' => $this->getEventOption('FIRST_GRID_TASK_CREATION_TOUR_GUIDE'),
			]
		);

		if ($isActionAdd && $this->request['STAY_AT_PAGE']) // reopen form with the same parameters as the previous one
		{
			$initial = $this->hitState->exportFlat('INITIAL_TASK_DATA', '.');
			// todo: a little spike for tags, refactor that later
			if (array_key_exists('TAGS.0', $initial))
			{
				$initial['TAGS[0]'] = $initial['TAGS.0'];
				unset($initial['TAGS.0']);
			}

			$url = Util::replaceUrlParameters($url, $initial, array_keys($initial));
		}

		return $url;
	}

	private function createTemplate($taskId)
	{
		$request = $this->getRequest();
		$additional = $request['ADDITIONAL'];

		if(
			!$additional
			|| $additional['SAVE_AS_TEMPLATE'] !== 'Y'
		)
		{
			return;
		}

		if (!Tasks\Access\TemplateAccessController::can($this->userId, Tasks\Access\ActionDictionary::ACTION_TEMPLATE_CREATE))
		{
			$this->errors->addWarning(
				'SAVE_AS_TEMPLATE_ERROR',
				Loc::getMessage('TASKS_TT_SAVE_AS_TEMPLATE_ERROR_MESSAGE_PREFIX') . ': ' . Loc::getMessage('TASKS_TEMPLATE_CREATE_FORBIDDEN')
			);
			return;
		}

		$task = new \Bitrix\Tasks\Item\Task($taskId, $this->userId); // todo: use Task::getInstance($taskId, $this->userId) here, when ready
		if($task['REPLICATE'] === 'Y')
		{
			return;
		}

		// create template here
		$conversionResult = $task->transformToTemplate();
		if(!$conversionResult->isSuccess())
		{
			return;
		}

		$template = $conversionResult->getInstance();
		// take responsibles directly from query, because task can not have multiple responsibles

		$responsibles = $this->getResponsibles();
		$respIds = array();
		foreach($responsibles as $user)
		{
			$respIds[] = intval($user['ID']);
		}
		$template['RESPONSIBLES'] = $respIds;
		$template['SE_CHECKLIST'] = new Tasks\Item\Task\CheckList();

		// todo: move logic from \Bitrix\Tasks\Manager\Task\Template::manageTaskReplication() here,
		// todo: mark the entire Manager namespace as deprecated
		// $template['REPLICATE_PARAMS'] = $operation['ARGUMENTS']['data']['SE_TEMPLATE']['REPLICATE_PARAMS'];

		$saveResult = $template->save();

		if ($saveResult->isSuccess())
		{
			$checkListItems = TaskCheckListFacade::getByEntityId($taskId);
			$checkListItems = array_map(
				static function($item)
				{
					$item['COPIED_ID'] = $item['ID'];
					unset($item['ID']);
					return $item;
				},
				$checkListItems
			);

			$checkListRoots = TemplateCheckListFacade::getObjectStructuredRoots(
				$checkListItems,
				$template->getId(),
				$this->userId
			);
			foreach ($checkListRoots as $root)
			{
				/** @var CheckList $root */
				$checkListSaveResult = $root->save();
				if (!$checkListSaveResult->isSuccess())
				{
					$saveResult->loadErrors($checkListSaveResult->getErrors());
				}
			}

			\Bitrix\Tasks\Item\Access\Task\Template::grantAccessLevel($template->getId(), 'U'.$this->userId, 'full', array(
				'CHECK_RIGHTS' => false,
			));
		}

		if (!$saveResult->isSuccess())
		{
			$conversionResult->abortConversion();

			$saveResultErrorMessages = $saveResult->getErrors()->getMessages();
			foreach ($saveResultErrorMessages as $message)
			{
				$this->errors->addWarning(
					'SAVE_AS_TEMPLATE_ERROR',
					Loc::getMessage('TASKS_TT_SAVE_AS_TEMPLATE_ERROR_MESSAGE_PREFIX') . ': ' . $message
				);
			}
		}
	}

	/**
	 * @param $taskId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function createSubTasks($taskId): void
	{
		$tasks = [$taskId];
		$responsibles = $this->getResponsibles();

		if (count($responsibles) > 1)
		{
			$op = $this->getTaskActionResult();

			// create one more task for each responsible
			if (!empty($op['ARGUMENTS']['data']))
			{
				$fields = $op['ARGUMENTS']['data'];

				$checkListItems = array_filter($fields['SE_CHECKLIST'], static function($item) {
					return is_array($item);
				});
				$checkListItemsExists = (is_array($checkListItems) && !empty($checkListItems));
				unset($fields['SE_CHECKLIST']);

				if ($checkListItemsExists)
				{
					foreach ($checkListItems as $id => $item)
					{
						$checkListItems[$id]['ID'] = ($item['ID'] === 'null' ? null : (int)$item['ID']);
						$checkListItems[$id]['IS_COMPLETE'] = ($item['IS_COMPLETE'] === 'true' || $item['IS_COMPLETE'] === true);
						$checkListItems[$id]['IS_IMPORTANT'] = ($item['IS_IMPORTANT'] === 'true' || $item['IS_IMPORTANT'] === true);
					}
				}

				foreach ($responsibles as $user)
				{
					if ($fields[Task\Originator::getCode(true)]['ID'] == $user['ID'])
					{
						continue; // do not copy to creator
					}

					$subTask = Manager\Task::makeItem($fields, $this->userId);
					$subTask['RESPONSIBLE_ID'] = $user['ID'];
					$subTask['PARENT_ID'] = $taskId;
					$subTask['MULTITASK'] = 'N';

					$subResult = $subTask->transform(new Tasks\Item\Converter\Task\ToTask());
					if ($subResult->isSuccess())
					{
						$subResult = $subTask->save();
						if ($subResult->isSuccess())
						{
							$subTaskId = $subTask->getId();

							$commentPoster = Tasks\Comments\Task\CommentPoster::getInstance($subTaskId, $this->userId);
							$commentPoster->enableDeferredPostMode();
							$commentPoster->clearComments();

							if ($checkListItemsExists)
							{
								$checkListRoots = TaskCheckListFacade::getObjectStructuredRoots(
									$checkListItems,
									$subTaskId,
									$this->userId,
									'PARENT_NODE_ID'
								);

								foreach ($checkListRoots as $root)
								{
									/** @var CheckList $root */
									$checkListSaveResult = $root->save();
									if (!$checkListSaveResult->isSuccess())
									{
										$subResult->loadErrors($checkListSaveResult->getErrors());
									}
								}
							}

							$tasks[] = $subTaskId;
						}
					}
				}
			}
		}

		foreach ($tasks as $taskId)
		{
			$this->createSubTasksBySource($taskId);
		}
	}

	protected function createSubTasksBySource($taskId)
	{
		$source = $this->getDataSource();

		if (!(int)($source['ID'] ?? null))
		{
			return;
		}

		$replicator = null;
		// clone subtasks or create them by template
		if($source['TYPE'] == static::DATA_SOURCE_TEMPLATE)
		{
			$replicator = new Util\Replicator\Task\FromTemplate();
		}
		elseif($source['TYPE'] == static::DATA_SOURCE_TASK)
		{
			$replicator = new Util\Replicator\Task\FromTask();
		}

		if(!$replicator)
		{
			return;
		}

		$parameters = ['MULTITASKING' => false];

		if ($source['TYPE'] == static::DATA_SOURCE_TEMPLATE)
		{
			$templates = Util::getOption('propagate_to_sub_templates');
			if ($templates)
			{
				$templates = unserialize($templates, ['allowed_classes' => false]);
				if (in_array((int)$source['ID'], $templates))
				{
					$taskData = $this->arResult['ACTION_RESULT']['task_action']['ARGUMENTS']['data'];

					$parameters['RESPONSIBLE_ID'] = current($taskData['SE_RESPONSIBLE'])['ID'];
					$parameters['GROUP_ID'] = $taskData['SE_PROJECT']['ID'];
				}
			}
		}

		$result = $replicator->produceSub($source['ID'], $taskId, $parameters, $this->userId);

		foreach ($result->getErrors() as $error)
		{
			$this->errors->add($error->getCode(), Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), Util\Error::TYPE_ERROR);
		}
	}

	/**
	 * Allows to pass some of arParams through ajax request, according to the white-list
	 * @return mixed[]
	 */
	protected static function extractParamsFromRequest($request)
	{
		return array('ID' => $request['ID']); // DO NOT simply pass $request to the result, its unsafe
	}

	protected function getDataDefaults()
	{
		$stateFlags = $this->arResult['COMPONENT_DATA']['STATE']['FLAGS'];

		$rights = Task::getFullRights($this->userId);
		$data = array(
			'CREATED_BY' => 		$this->userId,
			Task\Originator::getCode(true) => array('ID' => $this->userId),
			Task\Responsible::getCode(true) => array(array('ID' => $this->arParams['USER_ID'])),
			'PRIORITY' => 			CTasks::PRIORITY_AVERAGE,
			'FORUM_ID' => 			CTasksTools::getForumIdForIntranet(), // obsolete
			'REPLICATE' => 			'N',

			'REQUIRE_RESULT' => $stateFlags['REQUIRE_RESULT'] ? 'Y' : 'N',
			'TASK_PARAM_3' => $stateFlags['TASK_PARAM_3'] ? 'Y' : 'N',
			'ALLOW_CHANGE_DEADLINE' => $stateFlags['ALLOW_CHANGE_DEADLINE'] ? 'Y' : 'N',
			'ALLOW_TIME_TRACKING' => $stateFlags['ALLOW_TIME_TRACKING'] ? 'Y' : 'N',
			'TASK_CONTROL' => $stateFlags['TASK_CONTROL'] ? 'Y' : 'N',
			'MATCH_WORK_TIME' => $stateFlags['MATCH_WORK_TIME'] ? 'Y' : 'N',

			'DESCRIPTION_IN_BBCODE' => 'Y', // new tasks should be always in bbcode
			'DURATION_TYPE' => CTasks::TIME_UNIT_TYPE_DAY,
			'DURATION_TYPE_ALL' => CTasks::TIME_UNIT_TYPE_DAY,

			'SE_PARAMETER' => [
				array('NAME' => 'PROJECT_PLAN_FROM_SUBTASKS', 'VALUE' => 'Y')
			],

			Manager::ACT_KEY => $rights
		);

		return array('DATA' => $data, 'CAN' => array('ACTION' => $rights));
	}

	/**
	 * Checks out for any pre-set variables in request, when open form
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDataRequest()
	{
		$this->hitState->set($this->request->toArray(), 'INITIAL_TASK_DATA');

		$data = [];

		// parent task
		$parentId = $this->hitState->get('INITIAL_TASK_DATA.PARENT_ID');
		if ($parentId)
		{
			$data[Task\ParentTask::getCode(true)] = ['ID' => $parentId];
		}

		// responsible
		$responsibleId = $this->hitState->get('INITIAL_TASK_DATA.RESPONSIBLE_ID');
		if ($responsibleId)
		{
			$data[Task\Responsible::getCode(true)][] = ['ID' => $responsibleId];
		}

		// auditors
		$auditors = $this->hitState->get('INITIAL_TASK_DATA.AUDITORS');
		if (is_array($auditors) && !empty($auditors))
		{
			foreach ($auditors as $auditorId)
			{
				if (($auditorId = trim($auditorId)) !== '')
				{
					$data[Task\Auditor::getCode(true)][] = ['ID' => $auditorId];
				}
			}
		}

		// project
		$projectFieldCode = Task\Project::getCode(true);
		if ($projectId = $this->hitState->get('INITIAL_TASK_DATA.GROUP_ID'))
		{
			$data[$projectFieldCode] = ['ID' => $projectId];
		}
		elseif ($projectId = (int)$this->arParams['GROUP_ID'])
		{
			$data[$projectFieldCode] = ['ID' => $projectId];
		}
		elseif ($parentId)
		{
			$parentTask = Bitrix\Tasks\Item\Task::getInstance($parentId, $this->userId);
			if ($parentTask && $parentTask['GROUP_ID'])
			{
				$data[$projectFieldCode] = ['ID' => $parentTask['GROUP_ID']];
			}
		}

		// title
		$title = ($this->hitState->get('INITIAL_TASK_DATA.TITLE') ?? '');
		if ($title !== '')
		{
			$data['TITLE'] = htmlspecialchars_decode($title, ENT_QUOTES);
		}

		// description
		$description = $this->hitState->get('INITIAL_TASK_DATA.DESCRIPTION');
		if ($description !== '')
		{
			$data['DESCRIPTION'] = $description;
		}

		$diskFiles = $this->hitState->get('INITIAL_TASK_DATA.' . Integration\Disk\UserField::getMainSysUFCode());
		if (!empty($diskFiles))
		{
			$data[Integration\Disk\UserField::getMainSysUFCode()] = $diskFiles;
		}

		// crm links
		$ufCrm = Integration\CRM\UserField::getMainSysUFCode();
		$crm = $this->hitState->get("INITIAL_TASK_DATA.{$ufCrm}");
		if ($crm !== '')
		{
			$data[$ufCrm] = [$crm];
		}

		$ufMail = Integration\Mail\UserField::getMainSysUFCode();
		$email = $this->hitState->get("INITIAL_TASK_DATA.{$ufMail}");
		if ($email > 0)
		{
			$data[$ufMail] = [$email];
		}

		// tags
		$tags = $this->hitState->get('INITIAL_TASK_DATA.TAGS');
		if (is_array($tags) && !empty($tags))
		{
			$trans = [];
			foreach ($tags as $tag)
			{
				if (($tag = trim($tag)) !== '')
				{
					$trans[] = ['NAME' => $tag];
				}
			}

			$data[Task\Tag::getCode(true)] = $trans;
		}

		// deadline
		$deadline = $this->hitState->get('INITIAL_TASK_DATA.DEADLINE');
		if (!empty($deadline))
		{
			$data['DEADLINE'] = $deadline;
		}

		// START_DATE_PLAN
		$startDatePlan = $this->hitState->get('INITIAL_TASK_DATA.START_DATE_PLAN');
		if (!empty($startDatePlan))
		{
			$data['START_DATE_PLAN'] = $startDatePlan;
		}

		// END_DATE_PLAN
		$endDatePlan = $this->hitState->get('INITIAL_TASK_DATA.END_DATE_PLAN');
		if (!empty($endDatePlan))
		{
			$data['END_DATE_PLAN'] = $endDatePlan;
		}

		// scenario
		$scenario = $this->request->get('SCENARIO');
		if ($scenario && Tasks\Internals\Task\ScenarioTable::isValidScenario($scenario))
		{
			$data['SCENARIO'] = $scenario;
		}

		return ['DATA' => $data];
	}

	private function getTaskDataForExistingTask(): ?array
	{
		$data = Task::get(
			$this->userId,
			$this->task->getId(),
			[
				'ENTITY_SELECT' => $this->arParams['SUB_ENTITY_SELECT'],
				'ESCAPE_DATA' => static::getEscapedData(), // do not delete
				'ERRORS' => $this->errors,
			]
		);
		$this->arResult['DATA']['CHECKLIST_CONVERTED'] = TaskCheckListConverterHelper::checkEntityConverted($this->task->getId());

		if ($this->errors->checkHasFatals())
		{
			return null;
		}

		if ($this->formData !== false) // is form submitted
		{
			// applying form data on top, what changed
			$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);
		}

		$group = Workgroup::getById($data['DATA']['GROUP_ID']);
		$this->arParams['IS_SCRUM_TASK'] = ($group && $group->isScrumProject());
		if ($this->arParams['IS_SCRUM_TASK'])
		{
			$itemService = new Tasks\Scrum\Service\ItemService();
			$epicService = new Tasks\Scrum\Service\EpicService();

			$scrumItem = $itemService->getItemBySourceId($this->task->getId());
			if ($scrumItem->getId())
			{
				$epic = $epicService->getEpic($scrumItem->getEpicId());
				if ($epic->getId())
				{
					$this->arResult['DATA']['SCRUM']['EPIC'] = $epic->toArray();
				}
			}
		}

		return $data;
	}

	private function getTaskDataForNewTask(): ?array
	{
		$this->arResult['DATA']['CHECKLIST_CONVERTED'] = true;

		$data = $this->getDataDefaults();

		if ($this->formData !== false) // is form submitted
		{
			// applying form data on top, what changed
			$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);

			$group = Workgroup::getById($data['DATA']['SE_PROJECT']['ID'] ?? 0);
			$this->arParams['IS_SCRUM_TASK'] = ($group && $group->isScrumProject());

			if ($this->arParams['IS_SCRUM_TASK'] && !empty($data['DATA']['EPIC']))
			{
				$epicService = new Tasks\Scrum\Service\EpicService();

				$epic = $epicService->getEpic($data['DATA']['EPIC']);
				if ($epic->getId())
				{
					$this->arResult['DATA']['SCRUM']['EPIC'] = $epic->toArray();
				}
			}

			return $data;
		}

		$error = false;
		$sourceData = [];

		$this->arResult['DATA']['FROM_TEMPLATE'] = null;

		try
		{
			// copy from template
			if ($templateId = (int)$this->request['TEMPLATE'])
			{
				$sourceData = $this->getTemplateSourceData($templateId);
				$this->arResult['DATA']['CHECKLIST_CONVERTED'] =
					TemplateCheckListConverterHelper::checkEntityConverted($templateId)
				;

				$this->arResult['DATA']['FROM_TEMPLATE'] = $templateId;
			}
			// copy from another task
			elseif ((int)$this->request['COPY'] || (int)$this->request['_COPY'])
			{
				$taskIdToCopy = (int)($this->request['COPY'] ?: $this->request['_COPY']);
				$sourceData = $this->getCopiedTaskSourceData($taskIdToCopy);
				$this->arResult['DATA']['CHECKLIST_CONVERTED'] =
					TaskCheckListConverterHelper::checkEntityConverted($taskIdToCopy)
				;
			}
			// get some from request
			else
			{
				$sourceData = $this->getDataRequest();
			}
		}
		catch (TasksException $e)
		{
			if (
				$e->checkOfType(TasksException::TE_ACCESS_DENIED)
				|| $e->checkOfType(TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE)
			)
			{
				$error = 'access';
			}
			else
			{
				$error = 'other';
			}
		}
		catch (\Bitrix\Tasks\AccessDeniedException $e)
		{
			$error = 'access';
		}

		if ($error !== false)
		{
			$errorKey = (
				$error === 'access' ? 'TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE_COPY' : 'TASKS_TT_COPY_READ_ERROR'
			);
			$this->errors->add('COPY_ERROR', Loc::getMessage($errorKey),Collection::TYPE_WARNING);
		}

		$data['DATA'] = Task::mergeData($sourceData['DATA'], $data['DATA']);
		$data['DATA'] = $this->autoChangeTaskData($data['DATA']);

		return $data;
	}

	private function getTemplateSourceData(int $templateId): array
	{
		$sourceData = $this->cloneDataFromTemplate($templateId);

		if ($ufCrmEntities = $this->request['UF_CRM_TASK'])
		{
			$sourceData['DATA']['UF_CRM_TASK'][] = $ufCrmEntities;
		}
		if ($tags = $this->request['TAGS'])
		{
			$currentTags = array_map('strtolower', $sourceData['DATA']['TAGS']);
			$tags = array_map('strtolower', explode(',', $tags));
			foreach ($tags as $tag)
			{
				if (!in_array($tag, $currentTags, true))
				{
					$sourceData['DATA']['TAGS'][] = $tag;
					$sourceData['DATA']['SE_TAG'][] = ['NAME' => $tag];
				}
			}
		}

		$driver = Driver::getInstance();
		$userFieldManager = $driver->getUserFieldManager();
		$attachedObjects = $userFieldManager->getAttachedObjectByEntity(
			'TASKS_TASK_TEMPLATE',
			$templateId,
			'UF_TASK_WEBDAV_FILES'
		);
		if (!empty($attachedObjects))
		{
			$sourceData['DATA']['DISK_ATTACHED_OBJECT_ALLOW_EDIT'] = reset($attachedObjects)->getAllowEdit();
		}

		$this->setDataSource(static::DATA_SOURCE_TEMPLATE, $this->request['TEMPLATE']);

		return $sourceData;
	}

	private function getCopiedTaskSourceData(int $taskIdToCopy): array
	{
		$sourceData = $this->cloneDataFromTask($taskIdToCopy);

		$localOffset = (new \DateTime())->getOffset();
		$userOffset =  \CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;
		$newOffset = ($offset > 0 ? '+' : '') . UI::formatTimeAmount($offset, 'HH:MI');

		$deadline = $sourceData['DATA']['DEADLINE'];
		if ($deadline && ($date = new Type\DateTime($deadline)))
		{
			$sourceData['DATA']['DEADLINE_ISO'] = mb_substr($date->format('c'), 0, -6) . $newOffset;
		}

		$this->setDataSource(static::DATA_SOURCE_TASK, $taskIdToCopy);

		return $sourceData;
	}

	private function autoChangeTaskData(array $data): array
	{
		$data = $this->autoChangeOriginator($data);

		if ($this->request['TEMPLATE'])
		{
			$data = $this->autoChangeResponsible($data);
			$data = $this->autoChangeParent($data);
			$data = $this->autoChangeGroup($data);
		}

		return $data;
	}

	private function autoChangeOriginator(array $data): array
	{
		$originatorKey = Task\Originator::getCode(true);
		$responsibles = $data[Task\Responsible::getCode(true)];

		if (
			$data[$originatorKey]['ID'] !== $this->userId
			&& (
				count($responsibles) > 1
				|| (count($responsibles) === 1 && $responsibles[0]['ID'] !== $this->userId)
			)
		)
		{
			$data[$originatorKey]['ID'] = $this->userId;
			$this->errors->addWarning(
				'AUTO_CHANGE_ORIGINATOR',
				Loc::getMessage('TASKS_TT_AUTO_CHANGE_ORIGINATOR')
			);
		}

		return $data;
	}

	private function autoChangeResponsible(array $data): array
	{
		$responsibleId = (int)$this->request['RESPONSIBLE_ID'];
		if ($responsibleId > 0)
		{
			$data[Task\Responsible::getCode(true)] = [['ID' => $responsibleId]];
			$this->errors->addWarning(
				'AUTO_CHANGE_RESPONSIBLE',
				Loc::getMessage('TASKS_TT_AUTO_CHANGE_RESPONSIBLE')
			);
		}

		return $data;
	}

	private function autoChangeParent(array $data): array
	{
		$parentId = (int)$this->request['PARENT_ID'];
		if ($parentId)
		{
			$data['PARENT_ID'] = $parentId;
			$data[Task\ParentTask::getCode(true)] = ['ID' => $parentId];
			$this->errors->addWarning('AUTO_CHANGE_PARENT', Loc::getMessage('TASKS_TT_AUTO_CHANGE_PARENT'));
		}

		return $data;
	}

	private function autoChangeGroup(array $data): array
	{
		$groupId = (int)$this->request['GROUP_ID'];
		if ($groupId > 0)
		{
			$data[Task\Project::getCode(true)] = ['ID' => $groupId];
			$this->errors->addWarning('AUTO_CHANGE_GROUP', Loc::getMessage('TASKS_TT_AUTO_CHANGE_GROUP'));

			return $data;
		}

		$parentId = (int)$this->request['PARENT_ID'];
		if ($parentId)
		{
			$parentTask = Bitrix\Tasks\Item\Task::getInstance($parentId, $this->userId);
			if ($parentTask && $parentTask['GROUP_ID'])
			{
				$data[Task\Project::getCode(true)] = ['ID' => $parentTask['GROUP_ID']];
				$this->errors->addWarning(
					'AUTO_CHANGE_PARENT_GROUP',
					Loc::getMessage('TASKS_TT_AUTO_CHANGE_PARENT_GROUP')
				);
			}
		}

		return $data;
	}

	// get some data and decide what goes to arResult
	protected function getData()
	{
		// todo: if we have not done any redirect after doing some actions, better re-check task accessibility here

		//TasksTaskFormState::reset();
		$this->arResult['COMPONENT_DATA']['STATE'] = static::getState();
		$this->arResult['COMPONENT_DATA']['OPEN_TIME'] = (new DateTime())->getTimestamp();
		$this->arResult['COMPONENT_DATA']['CALENDAR_EVENT_ID'] = $this->request->get('CALENDAR_EVENT_ID');
		$this->arResult['COMPONENT_DATA']['CALENDAR_EVENT_DATA'] = $this->request->get('CALENDAR_EVENT_DATA');

		$this->arResult['COMPONENT_DATA']['SOURCE_POST_ENTITY_TYPE'] = (string)$this->request->get('SOURCE_POST_ENTITY_TYPE');
		$this->arResult['COMPONENT_DATA']['SOURCE_ENTITY_TYPE'] = (string)$this->request->get('SOURCE_ENTITY_TYPE');
		$this->arResult['COMPONENT_DATA']['SOURCE_ENTITY_ID'] = (int)$this->request->get('SOURCE_ENTITY_ID');

		$this->arResult['COMPONENT_DATA']['FIRST_GRID_TASK_CREATION_TOUR_GUIDE'] =
			$this->request->get('FIRST_GRID_TASK_CREATION_TOUR_GUIDE')
		;

		$this->arParams['IS_SCRUM_TASK'] = false;

		$this->arResult['DATA']['SCRUM'] = [];
		$this->arResult['DATA']['SCRUM']['EPIC'] = [];

		// editing an existing task, get THIS task data
		if ($this->task !== null)
		{
			$data = $this->getTaskDataForExistingTask();
			if ($data === null)
			{
				return;
			}
		}
		else
		{
			// get from other sources: default task data, or other task data, or template data
			$data = $this->getTaskDataForNewTask();
		}

		// kanban stages
		if (
			isset($data['DATA']['GROUP_ID'])
			&& $data['DATA']['GROUP_ID'] > 0
		)
		{
			if (
				array_key_exists('IS_SCRUM_TASK', $this->arParams)
				&& $this->arParams['IS_SCRUM_TASK']
			)
			{
				$kanbanService = new KanbanService();

				$this->arResult['DATA']['STAGES'] = $kanbanService->getStagesToTask($data['DATA']['ID']);
				$data['DATA']['STAGE_ID'] = $kanbanService->getTaskStageId($data['DATA']['ID']);
			}
			else
			{
				$this->arResult['DATA']['STAGES'] = StagesTable::getStages($data['DATA']['GROUP_ID'], true);
			}

			$data['CAN']['ACTION']['SORT'] =
				Loader::includeModule('socialnetwork')
				&& SocialNetwork\Group::can($data['DATA']['GROUP_ID'], SocialNetwork\Group::ACTION_SORT_TASKS)
			;
		}
		else
		{
			$this->arResult['DATA']['STAGES'] = [];
			$data['CAN']['ACTION']['SORT'] = false;
		}

		$this->arResult['DATA']['TASK'] = $data['DATA'];
		$this->arResult['CAN']['TASK'] = $data['CAN'];
		$this->arResult['CAN_SHOW_MOBILE_QR_POPUP'] = $this->canShowMobileQrPopup($this->arResult['DATA']['TASK']);

		$this->arResult['COMPONENT_DATA']['IM_CHAT_ID'] = 0;
		$this->arResult['COMPONENT_DATA']['IM_MESSAGE_ID'] = 0;
		$request = $this->getRequest();
		if (
			isset($request['IM_CHAT_ID'])
			&& (int) $request['IM_CHAT_ID'] > 0
		)
		{
			$this->arResult['COMPONENT_DATA']['IM_CHAT_ID'] = (int) $request['IM_CHAT_ID'];
		}

		if (
			isset($request['IM_MESSAGE_ID'])
			&& (int) $request['IM_MESSAGE_ID'] > 0
		)
		{
			$this->arResult['COMPONENT_DATA']['IM_MESSAGE_ID'] = (int) $request['IM_MESSAGE_ID'];
		}

		// obtaining additional data: calendar settings, user fields
		$this->getDataAux();

		// collect related: tasks, users & groups
		$this->collectTaskMembers();
		$this->collectRelatedTasks();
		$this->collectProjects();
		$this->collectLogItems();
	}

	/**
	 * @param $itemId
	 * @return array
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @access private
	 */
	private function cloneDataFromTemplate($itemId)
	{
		$data = array();

		$template = new Tasks\Item\Task\Template($itemId, $this->userId);
		$result = $template->transform(new Tasks\Item\Converter\Task\Template\ToTask());
		if($result->isSuccess())
		{
			$data = Task::convertFromItem($result->getInstance());

			// exception for responsibles, it may be multiple in the form
			$responsibles = array(array('ID' => $template['RESPONSIBLE_ID']));
			if(!empty($template['RESPONSIBLES']))
			{
				$responsibles = array();
				foreach($template['RESPONSIBLES'] as $userId)
				{
					$responsibles[] = array('ID' => $userId);
				}
			}
			$data['SE_RESPONSIBLE'] = $responsibles;

			$checkListItems = TemplateCheckListFacade::getItemsForEntity($itemId, $this->userId);
			if ($checkListItems)
			{
				foreach (array_keys($checkListItems) as $id)
				{
					$checkListItems[$id]['COPIED_ID'] = $id;
					unset($checkListItems[$id]['ID']);
				}
			}
			$data['SE_CHECKLIST'] = $checkListItems;
		}

		return array('DATA' => $data);
	}

	/**
	 * @param $itemId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function cloneDataFromTask($itemId)
	{
		$data = [];

		$task = new Tasks\Item\Task($itemId, $this->userId);
		$result = $task->transform(new Tasks\Item\Converter\Task\ToTask());
		if ($result->isSuccess())
		{
			$data = Task::convertFromItem($result->getInstance());

			// exception for responsibles, it may be multiple in the form
			$data['SE_RESPONSIBLE'] = [
				['ID' => $task['RESPONSIBLE_ID']],
			];

			$checkListItems = TaskCheckListFacade::getItemsForEntity($itemId, $this->userId);
			foreach (array_keys($checkListItems) as $id)
			{
				$checkListItems[$id]['COPIED_ID'] = $id;
				$checkListItems[$id]['IS_COMPLETE'] = 'N';
				unset($checkListItems[$id]['ID']);
			}
			$data['SE_CHECKLIST'] = $checkListItems;

			$data = array_merge($data, $this->processDates($task));
		}

		return ['DATA' => $data];
	}

	private function processDates(Tasks\Item\Task $task): array
	{
		$result = [];

		/** @var Type\DateTime $createdDate */
		$createdDate = clone $task->get('CREATED_DATE');
		$createdDate->stripTime();

		$dates = [
			'DEADLINE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
		];
		foreach ($dates as $key)
		{
			if ($task->get($key))
			{
				/** @var Type\DateTime $dateDate */
				$dateDate = clone $task->get($key);
				$dateDate->stripTime();

				$diff = $createdDate->getDiff($dateDate);
				$daysDiff = $diff->days;
				$daysDiff = ($diff->invert ? -$daysDiff : +$daysDiff);

				($now = new Type\DateTime())->addDay($daysDiff);

				/** @var Type\DateTime $newDate */
				$newDate = clone $task->get($key);
				$newDate->setDate(
					$now->getYearGmt(),
					$now->getMonthGmt(),
					$now->getDayGmt()
				);

				$result[$key] = $newDate;
			}
		}

		return $result;
	}

	protected function getDataAux()
	{
		$this->arResult['AUX_DATA'] = array();
		$auxSelect = array_flip($this->arParams['AUX_DATA_SELECT']);

		$this->arResult['AUX_DATA']['COMPANY_WORKTIME'] = static::getCompanyWorkTime(!isset($auxSelect['COMPANY_WORKTIME']));

		if(isset($auxSelect['USER_FIELDS']))
		{
			$this->getDataUserFields();
		}
		if(isset($auxSelect['TEMPLATE']))
		{
			$this->getDataTemplates();
		}

		$this->arResult['AUX_DATA']['HINT_STATE'] = \Bitrix\Tasks\UI::getHintState();
		$this->arResult['AUX_DATA']['MAIL'] = array(
			//'FORWARD' => \Bitrix\Tasks\Integration\Mail\Task::getReplyTo($this->userId, $this->arResult['DATA']['TASK']['ID'], 'dummy', SITE_ID)
		);
		$this->arResult['AUX_DATA']['DISK_FOLDER_ID'] = Integration\Disk::getFolderForUploadedFiles($this->userId)->getData()['FOLDER_ID'];
		$this->arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();
		$this->arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'] = Util\Restriction\Bitrix24Restriction\Limit\RecurringLimit::isLimitExceeded();
	}

	protected function getDataTemplates()
	{
		// todo: use \Bitrix\Tasks\Item\Task\Template::find() here, and check rights
		$res = CTaskTemplates::GetList(
			array("ID" => "DESC"),
			array('BASE_TEMPLATE_ID' => false, '!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER),
			array('NAV_PARAMS' => array('nTopCount' => 10)),
			array(
				'USER_ID' => $this->userId,
				'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
			),
			array('ID', 'TITLE')
		);

		$templates = array();
		while($template = $res->fetch())
		{
			$templates[$template['ID']] = array(
				'ID' => $template['ID'],
				'TITLE' => $template['TITLE']
			);
		}

		$this->arResult['AUX_DATA']['TEMPLATE'] = $templates;
	}

	protected function getDataUserFields()
	{
		$this->arResult['AUX_DATA']['USER_FIELDS'] = static::getUserFields($this->task !== null ? $this->task->getId() : 0);

		// restore uf values from task data
		if(Type::isIterable($this->arResult['AUX_DATA']['USER_FIELDS']))
		{
			foreach($this->arResult['AUX_DATA']['USER_FIELDS'] as $ufCode => $ufDesc)
			{
				if(isset($this->arResult['DATA']['TASK'][$ufCode]))
				{
					$this->arResult['AUX_DATA']['USER_FIELDS'][$ufCode]['VALUE'] = $this->arResult['DATA']['TASK'][$ufCode];
				}
			}
		}
	}

	protected function collectTaskMembers()
	{
		$data = $this->arResult['DATA']['TASK'];

		$this->collectMembersFromArray(Task\Originator::extractPrimaryIndexes($data[Task\Originator::getCode(true)] ?? null));
		$this->collectMembersFromArray(Task\Responsible::extractPrimaryIndexes($data[Task\Responsible::getCode(true)] ?? null));
		$this->collectMembersFromArray(Task\Accomplice::extractPrimaryIndexes($data[Task\Accomplice::getCode(true)] ?? null));
		$this->collectMembersFromArray(Task\Auditor::extractPrimaryIndexes($data[Task\Auditor::getCode(true)] ?? null));
		$this->collectMembersFromArray(array(
			$this->arResult['DATA']['TASK']['CHANGED_BY'] ?? null,
			$this->userId
		));
	}

	protected function collectRelatedTasks()
	{
		if(
			isset($this->arResult['DATA']['TASK']['PARENT_ID'])
			&& $this->arResult['DATA']['TASK']['PARENT_ID']
		)
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK']['PARENT_ID'];
		}
		elseif(
			isset($this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)])
			&& $this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)]
		)
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)]['ID'];
		}

		if(
			isset($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE'])
			&& Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE'])
		)
		{
			$projdep = $this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE'];
			foreach($projdep as $dep)
			{
				$this->tasks2Get[] = $dep['DEPENDS_ON_ID'];
			}
		}

		if(
			isset($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK'])
			&& Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK'])
		)
		{
			$related = $this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK'];
			foreach($related as $task)
			{
				$this->tasks2Get[] = $task['ID'];
			}
		}
	}

	protected function collectProjects()
	{
		if(
			isset($this->arResult['DATA']['TASK']['GROUP_ID'])
			&& $this->arResult['DATA']['TASK']['GROUP_ID']
		)
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK']['GROUP_ID'];
		}
		elseif(
			isset($this->arResult['DATA']['TASK'][Task\Project::getCode(true)])
			&& $this->arResult['DATA']['TASK'][Task\Project::getCode(true)]
		)
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID'];
		}
//		$this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID'] = $this->arResult['DATA']['TASK']['GROUP_ID']; // ?
	}

	protected function collectLogItems()
	{
		if (
			!isset($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG'])
			|| !Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG'])
		)
		{
			return;
		}

		foreach ($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG'] as $record)
		{
			switch ($record['FIELD'])
			{
				case 'CREATED_BY':
				case 'RESPONSIBLE_ID':
					if ($record['FROM_VALUE'])
					{
						$this->users2Get[] = $record['FROM_VALUE'];
					}

					if ($record['TO_VALUE'])
					{
						$this->users2Get[] = $record['TO_VALUE'];
					}

					break;
				case 'AUDITORS':
				case 'ACCOMPLICES':
					if ($record['FROM_VALUE'])
					{
						$this->collectMembersFromArray(explode(',', $record['FROM_VALUE']));
					}

					if ($record['TO_VALUE'])
					{
						$this->collectMembersFromArray(explode(',', $record['TO_VALUE']));
					}
					break;

				case 'GROUP_ID':
					if ($record['FROM_VALUE'])
					{
						$this->groups2Get[] = intval($record['FROM_VALUE']);
					}

					if ($record['TO_VALUE'])
					{
						$this->groups2Get[] = intval($record['TO_VALUE']);
					}
					break;

				case 'PARENT_ID':
					if ($record['FROM_VALUE'])
					{
						$this->tasks2Get[] = intval($record['FROM_VALUE']);
					}

					if ($record['TO_VALUE'])
					{
						$this->tasks2Get[] = intval($record['TO_VALUE']);
					}
					break;

				case 'DEPENDS_ON':
					if ($record['FROM_VALUE'])
					{
						$this->collectTasksFromArray(explode(',', $record['FROM_VALUE']));
					}

					if ($record['TO_VALUE'])
					{
						$this->collectTasksFromArray(explode(',', $record['TO_VALUE']));
					}
					break;

				default:
					break;
			}
		}
	}

	protected function collectMembersFromArray($ids)
	{
		if(Type::isIterable($ids) && !empty($ids))
		{
			$this->users2Get = array_merge($this->users2Get, $ids);
		}
	}

	protected function collectTasksFromArray($ids)
	{
		if (Type::isIterable($ids) && !empty($ids))
		{
			$this->tasks2Get = array_merge($this->tasks2Get, $ids);
		}
	}

	protected function getReferenceData()
	{
		$this->arResult['DATA']['RELATED_TASK'] = static::getTasksData(
			$this->tasks2Get,
			$this->userId,
			$this->users2Get
		);
		$this->arResult['DATA']['GROUP'] = Group::getData($this->groups2Get, ['IMAGE_ID', 'AVATAR_TYPE']);
		$this->arResult['DATA']['USER'] = User::getData($this->users2Get);

		$this->getCurrentUserData();
		$this->checkIsNetworkTask();
	}

	protected function getCurrentUserData()
	{
		$currentUser = array('DATA' => $this->arResult['DATA']['USER'][$this->userId] ?? null);

		$currentUser['IS_SUPER_USER'] = \Bitrix\Tasks\Util\User::isSuper($this->userId);
		$roles = array(
			'ORIGINATOR' => false,
			'DIRECTOR' => false, // director usually is more than just originator, according to the subordination rules
		);
		if($this->task !== null)
		{
			try
			{
				$roles['ORIGINATOR'] =  $this->task['CREATED_BY'] == $this->userId;
				$roles['DIRECTOR'] =    !!$this->task->isUserRole(\CTaskItem::ROLE_DIRECTOR);
			}
			catch(\TasksException $e)
			{
			}
		}
		$currentUser['ROLES'] = $roles;

		$this->arResult['AUX_DATA']['USER'] = $currentUser;
	}

	protected function checkIsNetworkTask(): void
	{
		$isNetworkTask = false;

		$taskData = $this->arResult['DATA']['TASK'] ?? [];
		$taskMembers = [];
		if (array_key_exists(Task\Originator::getCode(true), $taskData))
		{
			$taskMembers = array_merge($taskMembers, $taskData[Task\Originator::getCode(true)]);
		}
		if (array_key_exists(Task\Responsible::getCode(true), $taskData))
		{
			$taskMembers = array_merge($taskMembers, $taskData[Task\Responsible::getCode(true)]);
		}
		if (array_key_exists(Task\Accomplice::getCode(true), $taskData))
		{
			$taskMembers = array_merge($taskMembers, $taskData[Task\Accomplice::getCode(true)]);
		}
		if (array_key_exists(Task\Auditor::getCode(true), $taskData))
		{
			$taskMembers = array_merge($taskMembers, $taskData[Task\Auditor::getCode(true)]);
		}
		$taskMembers = array_map('intval', $taskMembers);
		$taskMembers = array_unique($taskMembers);

		foreach ($this->arResult['DATA']['USER'] as $user)
		{
			if ($user['IS_NETWORK_USER'] && in_array((int)$user['ID'], $taskMembers, true))
			{
				$isNetworkTask = true;
				break;
			}
		}

		$this->arResult['DATA']['IS_NETWORK_TASK'] = $isNetworkTask;
	}

	protected function formatData()
	{
		$data =& $this->arResult['DATA']['TASK'];

		if(Type::isIterable($data))
		{
			Task::extendData($data, $this->arResult['DATA']);

			// left for compatibility
			$data[Task::SE_PREFIX.'PARENT'] = $data[Task\ParentTask::getCode(true)] ?? null;
		}
	}

	protected function doPreAction()
	{
		parent::doPreAction();

		$this->arResult['COMPONENT_DATA']['BACKURL'] = $this->getBackUrl();
	}

	protected function doPostAction()
	{
		parent::doPostAction();

		if ($this->errors->checkNoFatals())
		{
			if ($this->task != null)
			{
				$collector = Tasks\Internals\Counter\Collector\UserCollector::getInstance((int)CurrentUser::get()->getId());
				$this->arResult['DATA']['GROUP_VIEWED'] = [
					'UNREAD_MID' => $collector->getUnReadForumMessageByFilter([
						'id' => [
							$this->task->getId()
						]
					])
				];

				ViewedTable::set(
					$this->task->getId(),
					$this->userId,
					null,
					[
						'UPDATE_TOPIC_LAST_VISIT' => false,
						'IS_REAL_VIEW' => true,
					]
				);
				if ($this->arParams['PLATFORM'] === 'web')
				{
					$this->arResult['DATA']['EFFECTIVE'] = $this->getEffective();
				}

				if (Loader::includeModule('pull'))
				{
					\CPullWatch::Add($this->userId, "TASK_VIEW_{$this->task->getId()}", true);
				}
			}

			$this->getEventData(); // put some data to $arResult for emitting javascript event when page loads
			$this->arResult['COMPONENT_DATA']['HIT_STATE'] = $this->getHitState()->exportFlat();
		}
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getEffective(): array
	{
		$res = Tasks\Internals\Counter\EffectiveTable::getList([
			'filter' => [
				'TASK_ID' => $this->task->getId(),
				'=IS_VIOLATION' => 'Y',
			],
			'order' => ['DATETIME' => 'DESC'],
			'count_total' => true,
		]);

		return [
			'COUNT' => $res->getCount(),
			'ITEMS' => $res->fetchAll(),
		];
	}

	// this method should be called "addEventData" :(
	protected function getEventData()
	{
		// form had not been submitted at the current hit, or submitted successfully
		if (($this->formData !== false && !$this->success) || !$this->getEventTaskId())
		{
			return;
		}

		$eventTaskData = false;
		if ($this->task != null && $this->task->getId() == $this->getEventTaskId())
		{
			$eventTaskData = $this->dropSubEntitiesData($this->arResult['DATA']['TASK']);
		}
		else // have to get data manually
		{
			try
			{
				$eventTask = Task::get($this->userId, $this->getEventTaskId());
				if ($eventTask['ERRORS']->checkNoFatals())
				{
					$eventTaskData = $eventTask['DATA'];
				}
			}
			catch (Tasks\Exception $e)
			{
				// something went wrong - no access or something else. Just skip, what else to do?
			}
		}

		// happy end
		if (!empty($eventTaskData) && Type::isIterable($eventTaskData))
		{
			$eventTaskData['CHILDREN_COUNT'] = 0;
			$childrenCount = CTasks::GetChildrenCount([], $eventTaskData['ID'])->fetch();
			if ($childrenCount)
			{
				$eventTaskData['CHILDREN_COUNT'] = $childrenCount['CNT'];
			}

			$eventType = $this->getEventType();

			$this->arResult['DATA']['EVENT_TASK'] = $eventTaskData;
			$this->arResult['COMPONENT_DATA']['EVENT_TYPE'] = $eventType;
			$this->arResult['COMPONENT_DATA']['EVENT_OPTIONS'] = [
				'STAY_AT_PAGE' => ($eventType === 'UPDATE' ? true : $this->getEventOption('STAY_AT_PAGE')),
				'SCOPE' => $this->getEventOption('SCOPE'),
				'FIRST_GRID_TASK_CREATION_TOUR_GUIDE' => $this->getEventOption('FIRST_GRID_TASK_CREATION_TOUR_GUIDE'),
			];
		}
	}

	protected static function getTasksData(array $taskIds, $userId, &$users2Get)
	{
		$tasks = array();

		if(!empty($taskIds))
		{
			$taskIds = array_unique($taskIds);
			$parsed = array();
			foreach($taskIds as $taskId)
			{
				if(intval($taskId))
				{
					$parsed[] = $taskId;
				}
			}

			if(!empty($parsed))
			{
				$select = array("ID", "TITLE", "STATUS", "START_DATE_PLAN", "END_DATE_PLAN", "DEADLINE", "RESPONSIBLE_ID");

				[$list, $res] = CTaskItem::fetchList(
					$userId,
					array("ID" => "ASC"),
					array("ID" => $parsed),
					array(),
					$select
				);
				$select = array_flip($select);

				foreach($list as $item)
				{
					$data = $item->getData(false);
					$tasks[$data['ID']] = array_intersect_key($data, $select);

					$users2Get[] = $data['RESPONSIBLE_ID']; // get also these users
				}
			}
		}

		return $tasks;
	}

	protected static function getUserFields($entityId = 0, $entityName = 'TASKS_TASK')
	{
		return \Bitrix\Tasks\Util\UserField\Task::getScheme($entityId);
	}

	// dont turn it to true for new components
	protected static function getEscapedData()
	{
		return false;
	}

	// temporal
	private function dropSubEntitiesData(array $data)
	{
		foreach($data as $key => $value)
		{
			if(mb_strpos((string)$key, Manager::SE_PREFIX) === 0)
			{
				unset($data[$key]);
			}
		}

		return $data;
	}

	// todo: move the following private functions to $hitState

	private function setDataSource($type, $id)
	{
		if(($type == static::DATA_SOURCE_TEMPLATE || $type == static::DATA_SOURCE_TASK) && intval($id))
		{
			$this->arResult['COMPONENT_DATA']['DATA_SOURCE'] = array(
				'TYPE' => $type,
				'ID' => intval($id)
			);
		}
	}

	private function getDataSource()
	{
		return ($this->arResult['COMPONENT_DATA']['DATA_SOURCE'] ?? null);
	}

	private function getEventType()
	{
		if($this->eventType === false && (string) $this->request['EVENT_TYPE'] != '')
		{
			$this->eventType = $this->request['EVENT_TYPE'] == 'UPDATE' ? 'UPDATE' : 'ADD';
		}

		return $this->eventType;
	}

	private function setEventType($type)
	{
		$this->eventType = $type;
	}

	private function getEventOption($name)
	{
		if (Type::isIterable($this->request['EVENT_OPTIONS']) && isset($this->request['EVENT_OPTIONS'][$name]))
		{
			// does not make sense to (bool) options
			$this->eventOptions[$name] = $this->request['EVENT_OPTIONS'][$name];
		}

		return $this->eventOptions[$name];
	}

	private function setEventOption($name, $value)
	{
		$this->eventOptions[$name] = $value;
	}

	private function getEventTaskId()
	{
		if(intval($this->request['EVENT_TASK_ID']))
		{
			$this->eventTaskId = intval($this->request['EVENT_TASK_ID']);
		}

		return $this->eventTaskId;
	}

	private function setEventTaskId($taskId)
	{
		$this->eventTaskId = $taskId;
	}

	private function getBackUrl()
	{
		if((string) $this->request['BACKURL'] != '')
		{
			return $this->request['BACKURL'];
		}
		elseif(array_key_exists('BACKURL', $this->arParams))
		{
			return $this->arParams['BACKURL'];
		}
		// or else backurl will be defined somewhere like result_modifer, see below

		return false;
	}

	private function setResponsibles($users)
	{
		if(Type::isIterable($users))
		{
			$this->responsibles = \Bitrix\Tasks\Util\Type::normalizeArray($users);
		}
	}

	private function extractResponsibles(array $data)
	{
		$code = Task\Responsible::getCode(true);

		if(array_key_exists($code, $data))
		{
			return $data[$code];
		}
		return array();
	}

	private function getResponsibles()
	{
		if($this->responsibles !== false && Type::isIterable($this->responsibles))
		{
			return $this->responsibles;
		}
		else
		{
			return array();
		}
	}

	private static function inviteUsers(array &$users, Collection $errors)
	{
		foreach($users as $i => $user)
		{
			if(is_array($user) && !intval($user['ID']))
			{
				if((string) $user['EMAIL'] != '' && \check_email($user['EMAIL']))
				{
					$newId = \Bitrix\Tasks\Integration\Mail\User::create($user);
					if($newId)
					{
						$users[$i]['ID'] = $newId;
						SocialNetwork::setLogDestinationLast(['U' => [$newId]]);
					}
					else
					{
						$errors->add('USER_INVITE_FAIL', 'User has not been invited');
					}
				}
				elseif (\Bitrix\Tasks\Integration\SocialServices\User::isNetworkId($user['ID']))
				{
					$newId = \Bitrix\Tasks\Integration\SocialServices\User::create($user);
					if($newId)
					{
						$users[$i]['ID'] = $newId;
						SocialNetwork::setLogDestinationLast(['U' => [$newId]]);
					}
					else
					{
						$errors->add('USER_INVITE_FAIL', 'User has not been invited');
					}
				}
				else
				{
					unset($users[$i]); // bad structure
				}
			}
		}
	}

	public function getHitState()
	{
		return $this->hitState;
	}

	public static function getState()
	{
		return TasksTaskFormState::get();
	}

	/**
	 * @param int $taskId
	 * @param array $data
	 */
	private function updateTask(int $taskId, array $data)
	{
		try
		{
			\Bitrix\Tasks\Manager\Task::update(
				$this->userId,
				$taskId,
				$data,
				[
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errorCollection,
				]
			);
		}
		catch (TasksException $e)
		{
			$messages = @unserialize($e->getMessage(), ['allowed_classes' => false]);
			if (is_array($messages))
			{
				foreach ($messages as $message)
				{
					$this->errorCollection->add('TASK_EXCEPTION', $message['text'], false, ['ui' => 'notification']);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->errorCollection->add('UNKNOWN_EXCEPTION', Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), false, ['ui' => 'notification']);
		}
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function prepareMobileData(array $data, int $taskId): array
	{
		$task = TaskRegistry::getInstance()->get($taskId, true);

		if (
			array_key_exists('DEADLINE', $data)
			&& (
				(empty($data['DEADLINE']) && is_null($task['DEADLINE']))
				|| ($task['DEADLINE'] && $data['DEADLINE'] === $task['DEADLINE']->toString())
			)
		)
		{
			unset($data['DEADLINE']);
		}

		if (array_key_exists('SE_RESPONSIBLE', $data))
		{
			$members = $task['MEMBER_LIST'];
			$responsibles = [];
			foreach ($members as $member)
			{
				if ($member['TYPE'] !== Tasks\Internals\Task\MemberTable::MEMBER_TYPE_RESPONSIBLE)
				{
					continue;
				}
				$responsibles[] = (int) $member['USER_ID'];
			}

			$dataResponsibles = [];
			foreach ($data['SE_RESPONSIBLE'] as $responsible)
			{
				$dataResponsibles[] = (int) $responsible['ID'];
			}

			if (empty(array_diff($responsibles, $dataResponsibles)))
			{
				unset($data['SE_RESPONSIBLE']);
			}
		}
		return $data;
	}

	public static function getAllowedMethods()
	{
		return array(
			'setState',
		);
	}

	public static function setState(array $state = array())
	{
		TasksTaskFormState::set($state);
	}

	private function completeTask(int $taskId)
	{
		$task = \CTaskItem::getInstance($taskId, User::getId());
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
		$task = \CTaskItem::getInstance($taskId, User::getId());
		if (
			$task->checkAccess(ActionDictionary::ACTION_TASK_RENEW)
			|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
		)
		{
			$queryObject = \CTasks::getList(
				[],
				['ID' => $taskId, '=STATUS' => \CTasks::STATE_COMPLETED],
				['ID'],
				['USER_ID' => User::getId()]
			);
			if ($queryObject->fetch())
			{
				$task->renew();
			}
		}
	}

	private function getGroupInfo(int $groupId, array $task): array
	{
		if ($groupId === 0)
		{
			$groupId = $task['GROUP_ID'] ?? 0;
			if ($groupId === 0)
			{
				return [0, ''];
			}
		}

		$group = WorkgroupTable::getByPrimary($groupId)->fetchObject();
		if (is_null($group))
		{
			return [0, ''];
		}

		return [$groupId, $group->getName()];
	}

	private function canShowMobileQrPopup(array $task): bool
	{
		if (
			isset($task['SCENARIO_NAME'])
			&& $task['SCENARIO_NAME'] === Tasks\Internals\Task\ScenarioTable::SCENARIO_MOBILE
			&& !(new Tasks\Provider\Mobile())->isMobileAppInstalled()
		)
		{
			return true;
		}

		return false;
	}
}