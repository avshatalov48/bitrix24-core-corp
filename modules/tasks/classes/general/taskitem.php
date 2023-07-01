<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use \Bitrix\Tasks\Internals\Task\FavoriteTable;
use \Bitrix\Tasks\Task\DependenceTable;
use \Bitrix\Tasks\Integration;
use \Bitrix\Tasks\Integration\Rest\Task\UserField;
use \Bitrix\Tasks\Integration\Disk\Rest\Attachment;
use \Bitrix\Tasks\Util\Type\DateTime;
use \Bitrix\Tasks\Util\Calendar;
use \Bitrix\Tasks\Util\User;
use \Bitrix\Tasks\ActionFailedException;
use \Bitrix\Tasks\ActionNotAllowedException;
use \Bitrix\Tasks\ActionRestrictedException;
use \Bitrix\Tasks\Integration\Bizproc;
use \Bitrix\Tasks\Access\ActionDictionary;

Loc::loadMessages(__FILE__);

interface CTaskItemInterface
{
	public function getData($returnEscapedData = true);
	public function getTags();
	public function getFiles();
	public function getDependsOn();
	public function getAllowedActions();
	public function startExecution();
	public function pauseExecution();
	public function defer();
	public function complete();
	public function delete();
	public function update($arNewTaskData);
	public function accept();
	public function delegate($newResponsibleId);
	public function decline($reason = '');
	public function renew();
	public function approve();
	public function disapprove();
	public function getId();		// returns tasks id
	public function getExecutiveUserId();	// returns user id used for rights check

	/**
	 * @param $actionId
	 * @return mixed
	 * @deprecated
	 */
	public function isActionAllowed($actionId);
	public function stopWatch();		// exclude itself from auditors
	public function startWatch();		// include itself to auditors
	public function isUserRole($roleId);
	public function checkAccess($actionId); // see the full list of actions in ActionDictionary

	/**
	 * Remove file attached to task
	 *
	 * @param integer $fileId
	 * @throws TasksException
	 * @throws CTaskAssertException
	 */
	public function removeAttachedFile($fileId);

	/**
	 * @param integer $format one of constants:
	 * CTaskItem::DESCR_FORMAT_RAW - give description of task "as is" (HTML or BB-code, depends on task)
	 * CTaskItem::DESCR_FORMAT_HTML - always return HTML (even if task in BB-code)
	 * CTaskItem::DESCR_FORMAT_PLAIN_TEXT - always return plain text (all HTML/BBCODE tags are stripped)
	 * can be omitted. Value by default is CTaskItem::DESCR_FORMAT_HTML.
	 *
	 * @throws CTaskAssertException if invalid format value given
	 *
	 * @return string description of the task (HTML will be sanitized accord to task module settings)
	 */
	public function getDescription($format = CTaskItem::DESCR_FORMAT_HTML);
}


final class CTaskItem implements CTaskItemInterface, ArrayAccess
{
	// Actions
	const ACTION_ACCEPT     			= 0x01;
	const ACTION_DECLINE    			= 0x02;
	const ACTION_COMPLETE   			= 0x03;
	const ACTION_APPROVE    			= 0x04;	// closes task
	const ACTION_DISAPPROVE 			= 0x05;	// perform ACTION_RENEW
	const ACTION_START      			= 0x06;
	const ACTION_DELEGATE   			= 0x07;
	const ACTION_REMOVE     			= 0x08;
	const ACTION_EDIT       			= 0x09;
	const ACTION_DEFER      			= 0x0A;
	const ACTION_RENEW      			= 0x0B;	// switch tasks to new or accepted state (depends on subordination)
	const ACTION_CREATE     			= 0x0C;
	const ACTION_CHANGE_DEADLINE     	= 0x0D; // also means now "change DEADLINE + START_DATE_PLAN + END_DATE_PLAN"
	const ACTION_CHANGE_DIRECTOR     	= 0x10; // i.e. delegate
	const ACTION_PAUSE               	= 0x11;
	const ACTION_START_TIME_TRACKING 	= 0x12;

	// checklist supreme rules
	const ACTION_CHECKLIST_ADD_ITEMS = 0x0E;
	const ACTION_CHECKLIST_REORDER_ITEMS = 0x16;

	// elapsed time supreme rules
	const ACTION_ELAPSED_TIME_ADD    = 0x0F;

	// favorite
	const ACTION_ADD_FAVORITE        = 0x13;
	const ACTION_DELETE_FAVORITE     = 0x14;
	const ACTION_TOGGLE_FAVORITE     = 0x15;

	const ACTION_READ                = 0x17;
	const ACTION_RATE			 	 = 0x666;

	// Roles implemented for managers of users too.
	// So, if some user is responsible in the task, than his manager has responsible role too.
	const ROLE_NOT_A_MEMBER = 0x01;		// not a member of the task
	const ROLE_DIRECTOR     = 0x02;
	const ROLE_RESPONSIBLE  = 0x04;
	const ROLE_ACCOMPLICE   = 0x08;
	const ROLE_AUDITOR      = 0x10;

	const DESCR_FORMAT_RAW        = 0x01;		// give description of task "as is" (HTML or BB-code, depends on task)
	const DESCR_FORMAT_HTML       = 0x02;		// always return HTML (even if task in BB-code)
	const DESCR_FORMAT_PLAIN_TEXT = 0x03;		// always return plain text (all HTML/BBCODE tags are stripped)

	private static $instances = array();

	private static $bSocialNetworkModuleIncluded = null;

	private $taskId = false;
	private $executiveUserId = false;	// User id under which rights will be checked

	// Lazy init:
	private $arTaskData = null;		// Task data

	// Very lazy init (not inited on arTaskData init, inited on demand):
	private $arTaskTags           = null;
	private $arTaskFiles          = null;
	private $arTaskDependsOn      = null;		// Ids of tasks where current tasks depends on
	private $arTaskUserRoles      = null;		// Roles in task of executive user
	private $arTaskAllowedActions = null;		// Allowed actions on task
	private $arTaskDataEscaped    = null;
	private $arTaskFileAttachments = null;

	private $lastOperationResultData = [];
	private array $_errors = [];

	private static $accessController;
	private static $allowedActions;

	/**
	 * Pin the task in the Kanban stage for users.
	 * @param int $taskId Task id.
	 * @param array $taskData Task data.
	 * @return void
	 */
	private static function pinInStage($taskId, $taskData = array())
	{
		if (empty($taskData))
		{
			\Bitrix\Tasks\Kanban\StagesTable::pinInStage($taskId);
		}
		else
		{
			$newUsers = array();
			foreach (array('CREATED_BY', 'RESPONSIBLE_ID', 'AUDITORS', 'ACCOMPLICES') as $code)
			{
				if (isset($taskData[$code]))
				{
					if (!is_array($taskData[$code]))
					{
						$taskData[$code] = array($taskData[$code]);
					}
					$newUsers = array_merge($newUsers, $taskData[$code]);
				}
			}
			if (!empty($newUsers))
			{
				\Bitrix\Tasks\Kanban\StagesTable::pinInStage($taskId, $newUsers);
			}
		}
	}

	public function getLastOperationResultData($operation = false)
	{
		if($operation === false)
		{
			return $this->lastOperationResultData;
		}
		else
		{
			return $this->lastOperationResultData[$operation];
		}
	}

	/**
	 * CTaskItem constructor.
	 *
	 * @param $taskId
	 * @param $executiveUserId
	 * @throws CTaskAssertException
	 */
	public function __construct($taskId, $executiveUserId)
	{
		CTaskAssert::assertLaxIntegers($taskId);
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($taskId > 0);
		CTaskAssert::assert($executiveUserId > 0);

		$this->markCacheAsDirty();

		$this->taskId = (int)$taskId;
		$this->executiveUserId = (int)$executiveUserId;
	}


	/**
	 * @param $taskId
	 * @param $executiveUserId
	 * @return CTaskItem returns link to cached object or creates it.
	 * @throws CTaskAssertException
	 */
	public static function getInstance($taskId, $executiveUserId)
	{
		return self::getInstanceFromPool($taskId, $executiveUserId);
	}


	/**
	 * @param $taskId
	 * @param $executiveUserId
	 * @return CTaskItem returns link to cached object or creates it.
	 * @throws CTaskAssertException
	 */
	public static function getInstanceFromPool($taskId, $executiveUserId)
	{
		CTaskAssert::assertLaxIntegers($taskId);
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($taskId > 0);
		CTaskAssert::assert($executiveUserId > 0);

		$taskId = (int)$taskId;
		$executiveUserId = (int)$executiveUserId;

		$key = "{$taskId}|{$executiveUserId}";

		// Cache instance in pool
		if (!isset(self::$instances[$key]))
		{
			self::$instances[$key] = new self($taskId, $executiveUserId);
		}

		return self::$instances[$key];
	}


	private static function cacheInstanceInPool($taskId, $executiveUserId, $oTaskItemInstance)
	{
		CTaskAssert::assertLaxIntegers($taskId, $executiveUserId);
		CTaskAssert::assert( ($taskId > 0) && ($executiveUserId > 0) );

		$key = (int) $taskId . '|' . (int) $executiveUserId;

		// Cache instance in pool
		self::$instances[$key] = $oTaskItemInstance;
	}

	/**
	 * Create new task and return instance for it
	 *
	 * @param array $arNewTaskData New task fields.
	 * @param integer $executiveUserId Put 1 (admin) to skip rights check.
	 * @param array $parameters Additional parameters.
	 * @return CTaskItem object
	 * @throws CTaskAssertException
	 * @throws TasksException - on access denied, task not exists.
	 */
	public static function add($arNewTaskData, $executiveUserId, array $parameters = array())
	{
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($executiveUserId > 0);

		// Use of BB code by default, HTML is deprecated,
		// but supported for backward compatibility when tasks created
		// from template or as copy of old task with HTML-description.
		if (
			isset($arNewTaskData['DESCRIPTION_IN_BBCODE'])
			&& ($arNewTaskData['DESCRIPTION_IN_BBCODE'] === 'N')	// HTML mode requested
			&& isset($arNewTaskData['DESCRIPTION'])
			&& ($arNewTaskData['DESCRIPTION'] !== '')		// allow HTML mode if there is description
			&& (mb_strpos($arNewTaskData['DESCRIPTION'], '<') !== false)	// with HTML tags
		)
		{
			$arNewTaskData['DESCRIPTION_IN_BBCODE'] = 'N';			// Set HTML mode
		}
		else
			$arNewTaskData['DESCRIPTION_IN_BBCODE'] = 'Y';

		if ( ! isset($arNewTaskData['CREATED_BY']) )
			$arNewTaskData['CREATED_BY'] = $executiveUserId;

		// Check some conditions for non-admins
		if (
			( ! CTasksTools::IsAdmin($executiveUserId) )
			&& ( ! CTasksTools::IsPortalB24Admin($executiveUserId) )
		)
		{
			if (isset($arNewTaskData['GROUP_ID']) && ($arNewTaskData['GROUP_ID'] > 0) && \Bitrix\Tasks\Integration\Socialnetwork::includeModule())
			{

				if (
					! CSocNetFeaturesPerms::CanPerformOperation(
						$executiveUserId, SONET_ENTITY_GROUP,
						$arNewTaskData['GROUP_ID'], 'tasks', 'create_tasks'
					)
				)
				{
					throw new TasksException(
						serialize(array(array('text' => GetMessage('TASKS_TASK_CREATE_ACCESS_DENIED'), 'id' => 'ERROR_TASK_CREATE_ACCESS_DENIED'))),
						TasksException::TE_ACCESS_DENIED
					);
				}
			}
		}

		if (!\Bitrix\Tasks\Access\TaskAccessController::can($executiveUserId, ActionDictionary::ACTION_TASK_SAVE, null, \Bitrix\Tasks\Access\Model\TaskModel::createFromArray($arNewTaskData)))
		{
			throw new TasksException(
				serialize(array(array('text' => GetMessage('TASKS_TASK_CREATE_ACCESS_DENIED'), 'id' => 'ERROR_TASK_CREATE_ACCESS_DENIED'))),
				TasksException::TE_ACCESS_DENIED
			);
		}

		if ( ! array_key_exists('GUID', $arNewTaskData) )
			$arNewTaskData['GUID'] = CTasksTools::genUuid();

		$arParams = array_merge($parameters, array(
			'USER_ID'			   => $executiveUserId,
			'CHECK_RIGHTS_ON_FILES' => true
		));

		$o = new CTasks();
		/** @noinspection PhpDeprecationInspection */
		$rc = $o->Add($arNewTaskData, $arParams);
		if ( ! ($rc > 0) )
		{
			static::throwExceptionVerbose($o->GetErrors());
		}

		self::pinInStage($rc);

		try
		{
			$newTaskItem = new CTaskItem( (int) $rc, $executiveUserId);
			if (!isset($parameters['DISABLE_BIZPROC_RUN']))
			{
				Bizproc\Listener::onTaskAdd($rc, $newTaskItem->getData());
			}
		}
		catch (TasksException | CTaskAssertException $e)
		{
			static::throwExceptionVerbose();
		}

		return $newTaskItem;
	}

	/**
	 * Duplicate task and return an instance of the clone.
	 *
	 * @param mixed[] $overrideTaskData Task data needs to be overrided externally.
	 * @param mixed[] $parameters Various set of parameters.
	 *
	 * 		<li> CLONE_CHILD_TASKS boolean 		clone subtasks or not
	 * 		<li> CLONE_CHECKLIST_ITEMS boolean 	clone check list items or not
	 * 		<li> CLONE_TAGS boolean 			clone tags or not
	 * 		<li> CLONE_REMINDERS boolean 		clone reminders or not
	 * 		<li> CLONE_TASK_DEPENDENCY boolean	clone previous tasks or not
	 * 		<li> CLONE_FILES boolean			clone files or not
	 *
	 * @throws TasksException - on access denied, task not found.
	 * @throws CTaskAssertException.
	 * @throws Exception - on unexpected error.
	 *
	 * @return CTaskItem[]
	 *
	 * @deprecated Use Replicator instead
	 */
	public function duplicate($overrideTaskData = array(), $parameters = array(
		'CLONE_CHILD_TASKS' => true,
		'CLONE_CHECKLIST_ITEMS' => true,
		'CLONE_TAGS' => true,
		'CLONE_REMINDERS' => true,
		'CLONE_TASK_DEPENDENCY' => true,
		'CLONE_FILES' => true
	))
	{
		if(!is_array($overrideTaskData))
			$overrideTaskData = array();

		if(!is_array($parameters))
			$parameters = array();
		if(!isset($parameters['CLONE_CHILD_TASKS']))
			$parameters['CLONE_CHILD_TASKS'] = true;
		if(!isset($parameters['CLONE_CHECKLIST_ITEMS']))
			$parameters['CLONE_CHECKLIST_ITEMS'] = true;
		if(!isset($parameters['CLONE_TAGS']))
			$parameters['CLONE_TAGS'] = true;
		if(!isset($parameters['CLONE_REMINDERS']))
			$parameters['CLONE_REMINDERS'] = true;
		if(!isset($parameters['CLONE_TASK_DEPENDENCY']))
			$parameters['CLONE_TASK_DEPENDENCY'] = true;
		if(!isset($parameters['CLONE_FILES']))
			$parameters['CLONE_FILES'] = true;

		$result = array();

		try
		{
			$data = $this->getData(false); // ensure we have access to the task
		}
		catch(TasksException $e)
		{
			return $result;
		}

		if (!is_array($data))
		{
			return $result;
		}

		$data = array_merge($data, $overrideTaskData);

		unset(
			// drop unwanted
			$data['ID'],
			$data['GUID'],
			$data['STATUS'],
			$data['CHANGED_BY'],
			// detach forum, if any
			$data['FORUM_TOPIC_ID'],
			$data['COMMENTS_COUNT'],
			// clean dates
			$data['CREATED_DATE'],
			$data['CHANGED_DATE'],
			$data['VIEWED_DATE'],
			$data['STATUS_CHANGED_DATE'],
			$data['ACTIVITY_DATE']
		);

		$files = array();
		if(is_array($data['UF_TASK_WEBDAV_FILES']) && !empty($data['UF_TASK_WEBDAV_FILES']))
		{
			$files = $data['UF_TASK_WEBDAV_FILES'];
		}

		unset($data['UF_TASK_WEBDAV_FILES']);

		$taskDupId = 0;
		try
		{
			$clone = static::add($data, $this->getExecutiveUserId());
			$taskDupId = $clone->getId();
		}
		catch(Exception $e)
		{
		}

		if(intval($taskDupId))
		{
			$result[$clone->getId()] = $clone;

			if($parameters['CLONE_CHECKLIST_ITEMS'])
			{
				list($arChecklistItems, $arMetaData) = CTaskCheckListItem::fetchList($this, array('SORT_INDEX' => 'ASC'));
				unset($arMetaData);

				foreach ($arChecklistItems as $oChecklistItem)
				{
					$cliData = $oChecklistItem->getData();
					$cliCloneData = array(
						'TITLE' => 				$cliData['TITLE'],
						'IS_COMPLETE' => 		$cliData['IS_COMPLETE'],
						'SORT_INDEX' => 			$cliData['SORT_INDEX']
					);

					CTaskCheckListItem::add($clone, $cliCloneData);
				}
			}

			if($parameters['CLONE_TAGS'])
			{
				$tags = $this->getTags();
				if(is_array($tags))
				{
					foreach($tags as $tag)
					{
						if((string) $tag != '')
						{
							$oTag = new CTaskTags();
							$oTag->Add(array(
								'TASK_ID' => 	$taskDupId,
								'NAME' => 		$tag
							), $this->getExecutiveUserId());
						}
					}
				}
			}

			if($parameters['CLONE_REMINDERS'])
			{
				$res = CTaskReminders::GetList(false, array('TASK_ID' => $this->getId()));
				while($item = $res->fetch())
				{
					$item['TASK_ID'] = $taskDupId;
					$item['USER_ID'] = $this->getExecutiveUserId();

					$oReminder = new CTaskReminders();
					$oReminder->Add($item);
				}
			}

			if($parameters['CLONE_TASK_DEPENDENCY'])
			{
				$res = CTaskDependence::GetList(array(), array('TASK_ID' => $this->getId()));
				while($item = $res->fetch())
				{
					$depInstance = new CTaskDependence();
					if(is_array($item))
					{
						$depInstance->Add(array(
							'TASK_ID' => $taskDupId,
							'DEPENDS_ON_ID' => $item['DEPENDS_ON_ID']
						));
					}
				}
			}

			if($parameters['CLONE_FILES'] && !empty($files) && \Bitrix\Main\Loader::includeModule('disk'))
			{
				// find which files are new and which are old
				$old = array();
				$new = array();
				foreach($files as $fileId)
				{
					if((string) $fileId)
					{
						if(mb_strpos($fileId, 'n') === 0)
							$new[] = $fileId;
						else
							$old[] = $fileId;
					}
				}

				if(!empty($old))
				{
					$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
					$old = $userFieldManager->cloneUfValuesFromAttachedObject($old, $this->getExecutiveUserId());

					if(is_array($old) && !empty($old))
					{
						$new = array_merge($new, $old);
					}
				}

				if(!empty($new))
					$clone->update(array('UF_TASK_WEBDAV_FILES' => $new));
			}

			if($parameters['CLONE_CHILD_TASKS'])
			{
				$notifADWasDisabled = CTaskNotifications::disableAutoDeliver();

				$clones = $this->duplicateChildTasks($clone);
				if(is_array($clones))
				{
					foreach($clones as $cId => $cInst)
						$result[$cId] = $cInst;
				}

				if($notifADWasDisabled)
				{
					CTaskNotifications::enableAutoDeliver();
				}
			}
		}

		return $result;
	}

	/**
	 * Duplicate subtasks of the current task.
	 *
	 * @param CTaskItem $cloneTaskInstance An instance of task clone that subtasks will be attached to.
	 *
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 *
	 * @deprecated Use Replicator instead
	 */
	public function duplicateChildTasks($cloneTaskInstance)
	{
		CTaskAssert::assert($cloneTaskInstance instanceof CTaskItemInterface);

		$duplicates = array();

		try
		{
			$data = $this->getData(false);
		}
		catch (TasksException $e)
		{
			return $duplicates;
		}

		if($data)
		{
			// getting tree data and checking for dead loops
			$queue = array();
			$this->duplicateChildTasksLambda($this, $queue);

			$idMap = array();
			foreach($queue as $taskInstance)
			{
				$data = $taskInstance->getData();

				$cloneInstances = $taskInstance->duplicate(array(
					'PARENT_ID' => isset($idMap[$data['PARENT_ID']]) ? $idMap[$data['PARENT_ID']] : $cloneTaskInstance->getId()
				), array(
					'CLONE_CHILD_TASKS' => false
				));
				if(is_array($cloneInstances) && !empty($cloneInstances))
				{
					$cloneInstance = array_shift($cloneInstances);

					$idMap[$taskInstance->getId()] = $cloneInstance->getId();
					$duplicates[$taskInstance->getId()] = $cloneInstance;
				}
			}
		}

		return $duplicates;
	}

	protected function duplicateChildTasksLambda($parentTaskInstance, &$queue)
	{
		// have to walk task tree recursively, because no tree structure is currently provided
		list($items, $res) = static::fetchList($this->getExecutiveUserId(), array(), array('PARENT_ID' => $parentTaskInstance->getId()), array(), array('*', 'UF_*'));
		unset($res);
		foreach($items as $taskInstance)
		{
			if(isset($queue[$taskInstance->getId()]))
			{
				throw new TasksException(
					'An endless loop detected when attempting to duplicate subtasks (task '.intval($parentTaskInstance->getId()).' met twice)',
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
				);
			}

			$queue[$taskInstance->getId()] = $taskInstance;
			$this->duplicateChildTasksLambda($taskInstance, $queue);
		}
	}

	/**
	 * Create a task by a template.
	 *
	 * @param integer $templateId - Id of task template.
	 * @param integer $executiveUserId User id. Put 1 here to skip rights.
	 * @param mixed[] $overrideTaskData Task data needs to be overrided externally.
	 * @param mixed[] $parameters Various set of parameters.
	 *
	 * 		<li> TEMPLATE_DATA mixed[] 			pre-cached data, if available we can get rid of additional queries
	 * 		<li> CREATE_CHILD_TASKS boolean 	if false, sub-tasks wont be created
	 * 		<li> CREATE_MULTITASK boolean		if false, discards template rule of "copying task to several responsibles"
	 * 		<li> BEFORE_ADD_CALLBACK callable 		callback called before each task added, allows to modify data passed to CTaskItem::add()
	 *
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 * @deprecated Use Replicator instead
	 */
	public static function addByTemplate($templateId, $executiveUserId, $overrideTaskData = array(), $parameters = array(
		'TEMPLATE_DATA' => array(),
		'CREATE_CHILD_TASKS' => true,
		'CREATE_MULTITASK' => true,

		'BEFORE_ADD_CALLBACK' => null,
		'SPAWNED_BY_AGENT' => false
	))
	{
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($executiveUserId > 0);

		$templateId = (int) $templateId;
		if ( ! $templateId )
		{
			return array();	// template id not set
		}

		if(!is_array($overrideTaskData))
			$overrideTaskData = array();

		if(!is_array($parameters))
			$parameters = array();
		if(!isset($parameters['CREATE_CHILD_TASKS']))
			$parameters['CREATE_CHILD_TASKS'] = true;
		if(!isset($parameters['CREATE_MULTITASK']))
			$parameters['CREATE_MULTITASK'] = true;
		if(!isset($parameters['BEFORE_ADD_CALLBACK']))
			$parameters['BEFORE_ADD_CALLBACK'] = null;
		if(!isset($parameters['SPAWNED_BY_AGENT']))
			$parameters['SPAWNED_BY_AGENT'] = false;

		// read template data

		if(is_array($parameters['TEMPLATE_DATA']) && !empty($parameters['TEMPLATE_DATA']))
		{
			$arTemplate = $parameters['TEMPLATE_DATA'];
		}
		else
		{
			$arFilter   = array('ID' => $templateId);
			$rsTemplate = CTaskTemplates::GetList(array(), $arFilter, array(), array(), array('*', 'UF_*'));
			$arTemplate = $rsTemplate->Fetch();

			if ( ! $arTemplate )
			{
				return array();	// nothing to do
			}
		}

		$arTemplate = array_merge($arTemplate, $overrideTaskData);

		$checkListItems = TemplateCheckListFacade::getByEntityId($templateId);
		$arTemplate['CHECK_LIST'] = array_map(
			static function($item)
			{
				$item['COPIED_ID'] = $item['ID'];
				unset($item['ID']);
				return $item;
			},
			$checkListItems
		);

		//////////////////////////////////////////////
		//////////////////////////////////////////////
		//////////////////////////////////////////////

		unset($arTemplate['STATUS'], $arTemplate['SCENARIO']);

		$userTime = \Bitrix\Tasks\Util\User::getTime();

		$arFields = $arTemplate;

		$arFields['CREATED_DATE'] = \Bitrix\Tasks\UI::formatDateTime($userTime);
		$arFields['ACCOMPLICES'] = unserialize($arFields['ACCOMPLICES'], ['allowed_classes' => false]);
		$arFields['AUDITORS'] = unserialize($arFields['AUDITORS'], ['allowed_classes' => false]);
		$arFields['TAGS'] = unserialize($arFields['TAGS'], ['allowed_classes' => false]);
		$arFields['FILES'] = unserialize($arFields['FILES'], ['allowed_classes' => false]);
		$arFields['DEPENDS_ON'] = unserialize($arFields['DEPENDS_ON'], ['allowed_classes' => false]);
		$arFields['REPLICATE'] = 'N';
		$arFields['CHANGED_BY'] = $arFields['CREATED_BY'];
		$arFields['CHANGED_DATE'] = $arFields['CREATED_DATE'];
		$arFields['ACTIVITY_DATE'] = $arFields['CREATED_DATE'];

		if ( ! $arFields['ACCOMPLICES'] )
		{
			$arFields['ACCOMPLICES'] = array();
		}

		if ( ! $arFields['AUDITORS'] )
		{
			$arFields['AUDITORS'] = array();
		}

		unset($arFields['ID'], $arFields['REPLICATE'], $arFields['REPLICATE_PARAMS']);

		$datePlanValues = array('DEADLINE', 'START_DATE_PLAN', 'END_DATE_PLAN');
		foreach ($datePlanValues as $value)
		{
			if ($arTemplate[$value.'_AFTER'])
			{
				$newValue = $userTime + $arTemplate[$value.'_AFTER'];
				$arFields[$value] = \Bitrix\Tasks\UI::formatDateTime($newValue);
			}
		}

		$multitaskMode = false;
		if($parameters['CREATE_MULTITASK'])
		{
			$arFields['RESPONSIBLES'] = unserialize($arFields['RESPONSIBLES'], ['allowed_classes' => false]);

			// copy task to multiple responsibles
			if ($arFields['MULTITASK'] == 'Y' && !empty($arFields['RESPONSIBLES']))
			{
				$arFields['RESPONSIBLE_ID'] = $arFields['CREATED_BY'];
				$multitaskMode = true;
			}
			else
			{
				$arFields['RESPONSIBLES'] = array();
			}
		}
		else
		{
			$arFields['MULTITASK'] = 'N';
			$arFields['RESPONSIBLES'] = array();
		}

		$arFields['FORKED_BY_TEMPLATE_ID'] = $templateId;

		// add main task to the create list
		$tasksToCreate = array(
			$arFields
		);

		// if MULTITASK where set to Y, create a duplicate task for each of RESPONSIBLES
		if (!empty($arFields['RESPONSIBLES']))
		{
			$arFields['MULTITASK'] = 'N';

			foreach ($arFields['RESPONSIBLES'] as $responsible)
			{
				$arFields['RESPONSIBLE_ID'] = $responsible;
				$tasksToCreate[] = $arFields;
			}
		}

		// get sub-templates
		$subTasksToCreate = array();
		if($parameters['CREATE_CHILD_TASKS'] !== false)
		{
			$subTasksToCreate = static::getChildTemplateData($templateId);
		}

		$created = array();

		// first, create ROOT tasks
		$multitaskTaskId = false;
		$i = 0;
		foreach($tasksToCreate as $arFields)
		{
			if($multitaskMode && $i > 0) // assign parent
			{
				if($multitaskTaskId)
				{
					// all following tasks will be subtasks of a base task in case of MULTITASK was turned on
					$arFields['PARENT_ID'] = $multitaskTaskId;
				}
				else
				{
					break; // no child tasks will be created, because parent task failed to be created
				}
			}

			$add = true;
			if(is_callable($parameters['BEFORE_ADD_CALLBACK']))
			{
				$result = call_user_func_array($parameters['BEFORE_ADD_CALLBACK'], array(&$arFields));
				if($result === false)
				{
					$add = false;
				}
			}

			if($add)
			{
				$taskId = 0;
				try
				{
					$task = static::add($arFields, $executiveUserId, array(
						'SPAWNED_BY_AGENT' => !!$parameters['SPAWNED_BY_AGENT'],
						'CLONE_DISK_FILE_ATTACHMENT' => true
					));
					$taskId = $task->getId();
				}
				catch(Exception $e)
				{
				}

				if ($taskId)
				{
					$commentPoster = CommentPoster::getInstance($taskId, $executiveUserId);
					$commentPoster->enableDeferredPostMode();
					$commentPoster->clearComments();

					// increase replication count of our template
					if ($i === 0 && (bool)$parameters['SPAWNED_BY_AGENT'])
					{
						$templateInstance = new CTaskTemplates();
						$templateInstance->update($templateId, [
							'TPARAM_REPLICATION_COUNT' => (int)$arTemplate['TPARAM_REPLICATION_COUNT'] + 1,
						]);
					}

					// the first task should be mom in case of multitasking
					if ($i === 0 && $multitaskMode)
					{
						$multitaskTaskId = $taskId;
					}

					$checkListRoots = TaskCheckListFacade::getObjectStructuredRoots(
						$arTemplate['CHECK_LIST'],
						$taskId,
						$executiveUserId
					);
					foreach ($checkListRoots as $root)
					{
						/** @var CheckList $root */
						$root->save();
					}

					$taskInstance = static::getInstance($taskId, $executiveUserId);
					$created[$taskId] = $taskInstance;

					if(!empty($subTasksToCreate))
					{
						$notifADWasDisabled = CTaskNotifications::disableAutoDeliver();

						$createdSubtasks = $taskInstance->addChildTasksByTemplate($templateId, array(
							'CHILD_TEMPLATE_DATA' =>	$subTasksToCreate,

							// transfer some parameters
							'BEFORE_ADD_CALLBACK' =>	$parameters['BEFORE_ADD_CALLBACK'],
							'SPAWNED_BY_AGENT' =>		$parameters['SPAWNED_BY_AGENT'],
						));

						if($notifADWasDisabled)
						{
							CTaskNotifications::enableAutoDeliver();
						}

						if(is_array($createdSubtasks) && !empty($createdSubtasks))
						{
							foreach($createdSubtasks as $ctId => $ctInst)
							{
								$created[$ctId] = $ctInst;
							}
						}
					}
				}
			}

			$i++;
		}

		return $created;
	}

	/**
	 * Create sub-task by sub-templates of a certain root template.
	 *
	 * @param integer $templateId Id of task template.
	 * @param integer $taskId Id of task sub-tasks will attach to.
	 * @param mixed[] $parameters Various set of parameters.
	 *
	 * 		<li> CHILD_TEMPLATE_DATA mixed[] 		pre-cached data, if available we can get rid of additional queries
	 * 		<li> BEFORE_ADD_CALLBACK callable 		callback called before each task added, allows to modify data passed to CTaskItem::add()
	 *
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 *
	 * @deprecated Use Replicator instead
	 */
	public function addChildTasksByTemplate($templateId, $parameters = array(
		'CHILD_TEMPLATE_DATA' =>	array(),

		'BEFORE_ADD_CALLBACK' =>	null,
		'SPAWNED_BY_AGENT' =>		false
	))
	{
		$templateId = (int) $templateId;
		if ( ! $templateId )
			return array();	// template id not set

		$taskId = $this->getId();

		// ensure we have access to this task
		try
		{
			$data = $this->getData(false);
		}
		catch (TasksException $e)
		{
			return [];
		}


		if(is_array($data))
		{
			if(!is_array($parameters))
				$parameters = array();

			if(!isset($parameters['BEFORE_ADD_CALLBACK']))
				$parameters['BEFORE_ADD_CALLBACK'] = null;
			if(!isset($parameters['SPAWNED_BY_AGENT']))
				$parameters['SPAWNED_BY_AGENT'] = false;

			// CHILD_TEMPLATE_DATA is used to pass pre-cached data to a function to avoid unnecessary db quires
			if(!is_array($parameters['CHILD_TEMPLATE_DATA']) || empty($parameters['CHILD_TEMPLATE_DATA']))
				$parameters['CHILD_TEMPLATE_DATA'] = $this->getChildTemplateData($templateId);

			$created = array();

			if(!empty($parameters['CHILD_TEMPLATE_DATA']))
			{
				$templateId2TaskId = array($templateId => $taskId);
				$creationOrder = array();
				$walkQueue = array($templateId);
				$treeBundles = array();

				// restruct array to avioid recursion. we should NOT lay on ID values

				foreach($parameters['CHILD_TEMPLATE_DATA'] as $subTemplate)
				{
					$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];
				}

				while(!empty($walkQueue))
				{
					$topTemplate = array_shift($walkQueue);

					if(is_array($treeBundles[$topTemplate]))
					{
						foreach($treeBundles[$topTemplate] as $parent => $template)
						{
							$walkQueue[] = $template;
							$creationOrder[] = $template;
						}
					}
					unset($treeBundles[$topTemplate]);
				}

				foreach($creationOrder as $subTemplateId)
				{
					$data = $parameters['CHILD_TEMPLATE_DATA'][$subTemplateId];

					if(!intval($templateId2TaskId[$data['BASE_TEMPLATE_ID']])) // smth went wrong previously, skip this branch
						continue;

					$createdTasks = static::addByTemplate($subTemplateId, $this->getExecutiveUserId(), array('PARENT_ID' => $templateId2TaskId[$data['BASE_TEMPLATE_ID']]), array(
						'TEMPLATE_DATA' => $data,
						'CREATE_CHILD_TASKS' =>		false,
						'CREATE_MULTITASK' =>		false,

						'BEFORE_ADD_CALLBACK' =>	$parameters['BEFORE_ADD_CALLBACK'],
						'SPAWNED_BY_AGENT' =>		$parameters['SPAWNED_BY_AGENT'],
					));

					if(is_array($createdTasks) && !empty($createdTasks))
					{
						foreach($createdTasks as $ctId => $ctInst)
							$created[$ctId] = $ctInst;

						$firstTask = array_shift($createdTasks);
						if($firstTask instanceof static)
						{
							$templateId2TaskId[$subTemplateId] = $firstTask->getId(); // get only the first, because it is "main" task
						}
					}
				}
			}

			return $created;
		}
		else
			return array();
	}

	protected static function getChildTemplateData($templateId)
	{
		$templateId = (int) $templateId;
		if ( ! $templateId )
			return array();	// template id not set

		$subTasksToCreate = array();

		// todo: use Item here!!!
		$userId = \Bitrix\Tasks\Util\User::getAdminId(); // todo: deprecated
		$ufc = new \Bitrix\Tasks\Util\UserField\Task(); // todo: deprecated
		$scheme = array();
		if($ufc)
		{
			$scheme = $ufc->getScheme();
		}

		$res = CTaskTemplates::GetList(array('BASE_TEMPLATE_ID' => 'asc'), array('BASE_TEMPLATE_ID' => $templateId), false, array('INCLUDE_TEMPLATE_SUBTREE' => true), array('*', 'UF_*', 'BASE_TEMPLATE_ID'));
		while($item = $res->fetch())
		{
			if($item['ID'] == $templateId)
				continue;

			// also, try to set default uf values
			foreach($scheme as $field => $desc)
			{
				if(!array_key_exists($field, $item))
				{
					$default = $ufc->getDefaultValue($field, $userId);
					if($default !== null)
					{
						$item[$field] = $default;
					}
				}
			}

			$subTasksToCreate[$item['ID']] = $item;
		}

		// get check lists
		$res = \Bitrix\Tasks\Internals\Task\Template\CheckListTable::getListByTemplateDependency($templateId, array(
			'order' => array('SORT' => 'ASC'),
			'select' => array('ID', 'TEMPLATE_ID', 'IS_COMPLETE', 'SORT_INDEX', 'TITLE')
		));
		while($item = $res->fetch())
		{
			if(isset($subTasksToCreate[$item['TEMPLATE_ID']]))
			{
				$clId = $item['ID'];
				$tmpId = $item['TEMPLATE_ID'];
				unset($item['ID']);
				unset($item['TEMPLATE_ID']);
				$subTasksToCreate[$tmpId]['CHECK_LIST'][$clId] = $item;
			}
		}

		return $subTasksToCreate;
	}

	public function __wakeup()
	{
		$this->markCacheAsDirty();
	}


	public function __sleep()
	{
		$this->markCacheAsDirty();
		return (array('taskId', 'executiveUserId', 'arTaskData',
			'arTaskAllowedActions', 'arTaskUserRoles', 'arTaskTags',
			'arTaskFiles', 'arTaskDependsOn'
		));
	}


	// prevent clone of object
	private function __clone(){}


	public function getId()
	{
		return ($this->taskId);
	}


	public function getExecutiveUserId()
	{
		return ($this->executiveUserId);
	}

	public function checkCanRead(array $parameters = [])
	{
		return $this->checkAccess(ActionDictionary::ACTION_TASK_READ);
	}

	protected function checkCanReadThrowException()
	{
		if(!$this->checkCanRead())
		{
			$this->throwExceptionNotAccessible();
		}
	}

	/**
	 * Get task data (read from DB on demand)
	 */
	public function getData($returnEscapedData = true, array $parameters = [], bool $bCheckPermissions = true)
	{
		// Preload data, if it isn't in cache
		if ($this->arTaskData === null)
		{
			$this->markCacheAsDirty();

			// Load task data
			$defaultParams = [
				'USER_ID' => $this->executiveUserId,
				'returnAsArray' => true,
				'bSkipExtraData' => false,
			];
			$arParams = array_merge($defaultParams, $parameters);

			/** @noinspection PhpDeprecationInspection */
			$arTask = CTasks::getById($this->taskId, $bCheckPermissions, $arParams);
			if (!isset($arTask['ID']) || !is_array($arTask))
			{
				$this->throwExceptionNotAccessible();
			}

			$this->arTaskData = $arTask;
		}

		if ($returnEscapedData)
		{
			// Prepare escaped data on-demand
			if ($this->arTaskDataEscaped === null)
			{
				foreach ($this->arTaskData as $field => $value)
				{
					$this->arTaskDataEscaped['~' . $field] = $value;

					if ($field === 'DESCRIPTION')
					{
						$this->arTaskDataEscaped[$field] = $this->getDescription();
					}
					elseif (is_numeric($value) || (!is_string($value)))
					{
						$this->arTaskDataEscaped[$field] = $value;
					}
					else
					{
						$this->arTaskDataEscaped[$field] = htmlspecialcharsex($value);
					}
				}
			}

			$returnData = $this->arTaskDataEscaped;
		}
		else
		{
			$returnData = $this->arTaskData;
		}

		return $returnData;
	}

	/**
	 * @param int $format
	 * @return mixed|null|string
	 * @throws CTaskAssertException
	 *
	 * @deprecated
	 */
	public function getDescription($format = self::DESCR_FORMAT_HTML)
	{
		$rc = null;

		$format = intval($format);

		CTaskAssert::assert(in_array(
			$format,
			array(self::DESCR_FORMAT_RAW, self::DESCR_FORMAT_HTML, self::DESCR_FORMAT_PLAIN_TEXT),
			true
		));

		try
		{
			$arTask = $this->getData($bSpecialChars = false);
		}
		catch (TasksException $e)
		{
			CTaskAssert::assert(false);
		}

		$description = $arTask['DESCRIPTION'];

		if ($format === self::DESCR_FORMAT_RAW)
			return ($description);

		// Now, convert description to HTML
		if ($arTask['DESCRIPTION_IN_BBCODE'] === 'Y')
		{
			// safe BBCODE to safe HTML
			$parser = new CTextParser();
			$description = str_replace(
				"\t",
				' &nbsp; &nbsp;',
				$parser->convertText($description)
			);
		}
		else
		{
			// unsafe HTML to safe HTML
			$description = CTasksTools::SanitizeHtmlDescriptionIfNeed($description);
		}

		if ($format === self::DESCR_FORMAT_HTML)
			$rc = $description;
		elseif ($format === self::DESCR_FORMAT_PLAIN_TEXT)
		{
			$rc = strip_tags(
				str_replace(
					array('<br>', '<br/>', '<br />'),
					"\n",
					$description
				)
			);
		}
		else
		{
			CTaskAssert::log(
				'CTaskItem->getTaskDescription(): unexpected format: ' . $format,
				CTaskAssert::ELL_ERROR
			);

			CTaskAssert::assert(false);
		}

		return ($rc);
	}


	public function getTags()
	{
		// ensure we have access to the task
		$this->checkCanReadThrowException();

		if ($this->arTaskTags === null)
		{
			$rsTags = CTaskTags::GetList(
				[],
				['TASK_ID' => $this->taskId]
			);

			$arTags = array();

			while ($arTag = $rsTags->fetch())
				$arTags[] = $arTag['NAME'];

			$this->arTaskTags = $arTags;
		}

		return ($this->arTaskTags);
	}


	/**
	 * @deprecated
	 */
	public function getAllowedTaskActions()
	{
		return ($this->getAllowedActions());
	}


	/**
	 * @deprecated
	 */
	public function getAllowedTaskActionsAsStrings()
	{
		return ($this->getAllowedActions($bReturnAsStrings = true));
	}


	public function getAllowedActions($bReturnAsStrings = false)
	{
		if ($bReturnAsStrings)
		{
			return ($this->getAllowedActionsAsStrings());
		}

		if ($this->arTaskAllowedActions !== null)
		{
			return ($this->arTaskAllowedActions);
		}

		// Lazy load and cache allowed actions list
		try
		{
			$this->arTaskAllowedActions = self::getAllowedActionsArrayInternal(
				$this->executiveUserId,
				$this->getData($bSpecialChars = false)
			);
		}
		catch (TasksException $e)
		{
			return [];
		}

		return $this->arTaskAllowedActions;
	}

	public static function getAllowedActionsArray($executiveUserId, array $taskData, $returnAsString = false)
	{
		$actions = self::getAllowedActionsArrayInternal(
			$executiveUserId,
			$taskData,
			self::getUserRolesArray($executiveUserId, $taskData)
		);

		if ($returnAsString)
		{
			return self::getAllowedActionsAsStringsStatic($actions);
		}

		return $actions;
	}

	private static function getAllowedActionsArrayInternal($executiveUserId, array $arTaskData, $bmUserRoles = null)
	{
		$taskId = (int) $arTaskData['ID'];
		if (
			!isset(self::$allowedActions[$taskId])
			|| !is_array(self::$allowedActions[$taskId])
			|| !array_key_exists($executiveUserId, self::$allowedActions[$taskId])
		)
		{
			$actionMap = ActionDictionary::getLegacyActionMap();
			$request = [];
			foreach ($actionMap as $legacyId => $action)
			{
				$request[$action] = [];
			}

			$taskModel = \Bitrix\Tasks\Access\Model\TaskModel::createFromId($taskId);
			try
			{
				$rights = self::getAccessController((int) $executiveUserId)->batchCheck($request, $taskModel);
			}
			catch (\Bitrix\Main\Access\Exception\AccessException $e)
			{
				return [];
			}

			self::$allowedActions[$taskId][$executiveUserId] = [];
			foreach ($actionMap as $legacyId => $action)
			{
				if (array_key_exists($action, $rights) && $rights[$action])
				{
					self::$allowedActions[$taskId][$executiveUserId][] = $legacyId;
				}
			}
		}

		return self::$allowedActions[$taskId][$executiveUserId];
	}

	public static function getAllowedActionsMap()
	{
		static $arStringsMap = array(
			self::ACTION_ACCEPT     				=> 'ACTION_ACCEPT',
			self::ACTION_DECLINE    				=> 'ACTION_DECLINE',
			self::ACTION_COMPLETE   				=> 'ACTION_COMPLETE',
			self::ACTION_APPROVE    				=> 'ACTION_APPROVE',
			self::ACTION_DISAPPROVE 				=> 'ACTION_DISAPPROVE',
			self::ACTION_START      				=> 'ACTION_START',
			self::ACTION_PAUSE      				=> 'ACTION_PAUSE',
			self::ACTION_DELEGATE   				=> 'ACTION_DELEGATE',
			self::ACTION_REMOVE     				=> 'ACTION_REMOVE',
			self::ACTION_EDIT       				=> 'ACTION_EDIT',
			self::ACTION_DEFER      				=> 'ACTION_DEFER',
			self::ACTION_RENEW      				=> 'ACTION_RENEW',
			self::ACTION_CREATE     				=> 'ACTION_CREATE',
			self::ACTION_CHANGE_DEADLINE        	=> 'ACTION_CHANGE_DEADLINE',
			self::ACTION_CHECKLIST_ADD_ITEMS    	=> 'ACTION_CHECKLIST_ADD_ITEMS',
			self::ACTION_CHECKLIST_REORDER_ITEMS    => 'ACTION_CHECKLIST_REORDER_ITEMS',
			self::ACTION_CHANGE_DIRECTOR        	=> 'ACTION_CHANGE_DIRECTOR',
			self::ACTION_ELAPSED_TIME_ADD       	=> 'ACTION_ELAPSED_TIME_ADD',
			self::ACTION_START_TIME_TRACKING    	=> 'ACTION_START_TIME_TRACKING',
			self::ACTION_ADD_FAVORITE           	=> 'ACTION_ADD_FAVORITE',
			self::ACTION_DELETE_FAVORITE        	=> 'ACTION_DELETE_FAVORITE',
			self::ACTION_RATE						=> 'ACTION_RATE'
		);

		return $arStringsMap;
	}

    public static function getStatusMap()
    {
        static $arStringsMap = array(
            CTasks::METASTATE_VIRGIN_NEW          => 'METASTATE_VIRGIN_NEW',
            CTasks::METASTATE_EXPIRED             => 'METASTATE_EXPIRED',
            CTasks::METASTATE_EXPIRED_SOON             => 'METASTATE_EXPIRED_SOON',
            CTasks::STATE_NEW                     => 'STATE_NEW',
            CTasks::STATE_PENDING                 => 'STATE_PENDING',
            CTasks::STATE_IN_PROGRESS             => 'STATE_IN_PROGRESS',
            CTasks::STATE_SUPPOSEDLY_COMPLETED    => 'STATE_SUPPOSEDLY_COMPLETED',
            CTasks::STATE_COMPLETED               => 'STATE_COMPLETED',
            CTasks::STATE_DEFERRED                => 'STATE_DEFERRED',
            CTasks::STATE_DECLINED                => 'STATE_DECLINED',
        );

        return $arStringsMap;
    }

	private function getAllowedActionsAsStrings($allowedActions = false): array
	{
		if ($allowedActions === false)
		{
			$allowedActions = $this->getAllowedActions();
		}

		return self::getAllowedActionsAsStringsStatic($allowedActions);
	}

	private static function getAllowedActionsAsStringsStatic($allowedActions = false): array
	{
		$arResult = [];

		$actionsMap = self::getAllowedActionsMap();
		foreach ($actionsMap as $actionCode => $actionString)
		{
			$arResult[$actionString] = in_array($actionCode, $allowedActions, true);
		}

		return $arResult;
	}

	private static function getAccessController(int $executiveUserId): \Bitrix\Main\Access\AccessibleController
	{
		if (!self::$accessController || !array_key_exists($executiveUserId, self::$accessController))
		{
			self::$accessController[$executiveUserId] = new \Bitrix\Tasks\Access\TaskAccessController((int) $executiveUserId);
		}
		return self::$accessController[$executiveUserId];
	}

	public function checkAccess($action, $params = null): bool
	{
		$taskModel = \Bitrix\Tasks\Access\Model\TaskModel::createFromId((int) $this->getId());

		try
		{
			$res = self::getAccessController((int) $this->executiveUserId)->check($action, $taskModel, $params);
		}
		catch (\Bitrix\Main\Access\Exception\AccessException $e)
		{
			$res = false;
		}

		return $res;
	}

	/**
	 * @param $actionId
	 * @return bool|mixed
	 * @deprecated since 20.6.0
	 */
	public function isActionAllowed($actionId)
	{
		$action = ActionDictionary::getActionByLegacyId($actionId);
		if (!$action)
		{
			return false;
		}

		return $this->checkAccess($action);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function delete(array $params=array())
	{
		$this->proceedAction(self::ACTION_REMOVE, array('PARAMETERS' => $params));
	}


	/**
	 * Delegate task to some responsible person (only subordinate users allowed)
	 *
	 * @param integer $newResponsibleId user id of new responsible person
	 * @throws TasksException, including codes TE_TRYED_DELEGATE_TO_WRONG_PERSON,
	 * TE_ACTION_NOT_ALLOWED, TE_ACTION_FAILED_TO_BE_PROCESSED,
	 * TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE
	 */
	public function delegate($newResponsibleId, array $params=array())
	{
		$this->proceedAction(
			self::ACTION_DELEGATE,
			[
				'RESPONSIBLE_ID' => $newResponsibleId,
				'PARAMETERS' => $params
			]
		);

		self::pinInStage($this->getId(), ['RESPONSIBLE_ID' => $newResponsibleId]);
	}


	/**
	 * Decline task
	 *
	 * @param string $reason reason by which task declined
	 * @throws TasksException, including codes TE_ACTION_NOT_ALLOWED,
	 * TE_ACTION_FAILED_TO_BE_PROCESSED,
	 * TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE
	 *
	 * @deprecated
	 */
	public function decline($reason = '')
	{
		$this->proceedAction(
			self::ACTION_DECLINE,
			array('DECLINE_REASON' => $reason)
		);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function startExecution(array $params=array())
	{
		$this->proceedAction(self::ACTION_START, ['PARAMETERS' => $params]);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function pauseExecution(array $params=array())
	{
		$this->proceedAction(self::ACTION_PAUSE, ['PARAMETERS' => $params]);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function defer(array $params=array())
	{
		$this->proceedAction(self::ACTION_DEFER, ['PARAMETERS' => $params]);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function complete(array $params=array())
	{
		$this->proceedAction(self::ACTION_COMPLETE, ['PARAMETERS' => $params]);
	}


	public function update($arNewTaskData = [], array $parameters = array())
	{
		if (empty($arNewTaskData))
		{
			return;
		}

		if (
			!array_key_exists('PIN_IN_STAGE', $parameters) ||
			array_key_exists('PIN_IN_STAGE', $parameters) && $parameters['PIN_IN_STAGE']
		)
		{
			self::pinInStage($this->getId(), $arNewTaskData);
		}

		$this->proceedAction(
			self::ACTION_EDIT,
			array('FIELDS' => $arNewTaskData, 'PARAMETERS' => $parameters)
		);

		// drop gmt cache
		$this->startDatePlanGmt = 	null;
		$this->endDatePlanGmt = 	null;
	}


	/**
	 * Remove $userId (or executive user) from the auditor list for the task
	 *
	 * @param integer $userId
	 * @throws TasksException
	 */
	public function stopWatch($userId = 0)
	{
		// Force reload cache
		$this->markCacheAsDirty();

		try
		{
			$arTask = $this->getData($bEscaped = false);
		}
		catch (TasksException $e)
		{
			static::throwExceptionVerbose();
		}

		if(!($userId = intval($userId)))
		{
			$userId = $this->executiveUserId;
		}

		$key = array_search($userId, $arTask['AUDITORS']);

		// Am I auditor?
		if ($key !== false)
		{
			unset($arTask['AUDITORS'][$key]);
			$arFields = array('AUDITORS' => $arTask['AUDITORS']);
			$this->markCacheAsDirty();
			$o = new CTasks();
			$arParams = array(
				'USER_ID'               => $this->executiveUserId,
				'CHECK_RIGHTS_ON_FILES' => true
			);

			/** @noinspection PhpDeprecationInspection */
			if ($o->update($this->taskId, $arFields, $arParams) !== true)
			{
				static::throwExceptionVerbose($o->GetErrors());
			}
		}
	}

	/**
	 * Add $userId (or executive user) to the auditor list for the task
	 *
	 * @param integer $userId
	 * @throws TasksException
	 */
	public function startWatch($userId = 0, $bSkipNotification = false)
	{
		// Force reload cache
		$this->markCacheAsDirty();

		try
		{
			$arTask = $this->getData($bEscaped = false);
		}
		catch (TasksException $e)
		{
			static::throwExceptionVerbose();
		}

		if(!($userId = intval($userId)))
		{
			$userId = $this->executiveUserId;
		}

		self::pinInStage($this->getId(), array(
			'AUDITORS' => $userId
		));

		// Am I auditor?
		if ( ! in_array($userId, $arTask['AUDITORS']))
		{
			$arTask['AUDITORS'][] = $userId;
			$arFields = array('AUDITORS' => $arTask['AUDITORS']);
			$this->markCacheAsDirty();
			$o = new CTasks();
			$arParams = array(
				'USER_ID'               => $this->executiveUserId,
				'CHECK_RIGHTS_ON_FILES' => true,
				'SKIP_NOTIFICATION' => $bSkipNotification
			);

			/** @noinspection PhpDeprecationInspection */
			if ($o->update($this->taskId, $arFields, $arParams) !== true)
			{
				static::throwExceptionVerbose($o->GetErrors());
			}
		}
	}


	/**
	 * @deprecated
	 */
	public function accept()
	{
		$this->proceedAction(self::ACTION_ACCEPT);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function renew(array $params = array())
	{
		$this->proceedAction(self::ACTION_RENEW, ['PARAMETERS' => $params]);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function approve(array $params = array())
	{
		$this->proceedAction(self::ACTION_APPROVE, ['PARAMETERS' => $params]);
	}

	/**
	 * @param array $params
	 *
	 * @throws TasksException
	 */
	public function disapprove(array $params = array())
	{
		$this->proceedAction(self::ACTION_DISAPPROVE, ['PARAMETERS' => $params]);
	}

	/**
	 * Adds a task to favorites for the current user.
	 *
	 * @param mixed[] Behaviour flags
	 *
	 *  <li> AFFECT_CHILDREN boolean if true, all child tasks also will be added to favorite. (default false)
	 *
	 * @return boolean
	 */
	public function addToFavorite($parameters = array('AFFECT_CHILDREN' => true))
	{
		return $this->proceedAction(self::ACTION_ADD_FAVORITE, $parameters);
	}

	/**
	 * Removes a task from favorites for the current user.
	 *
	 * @param mixed[] Behaviour flags
	 *
	 *  <li> AFFECT_CHILDREN boolean if true, all child tasks also will be added to favorite. (default false)
	 *
	 * @return boolean
	 */
	public function deleteFromFavorite($parameters = array('AFFECT_CHILDREN' => true))
	{
		return $this->proceedAction(self::ACTION_DELETE_FAVORITE, $parameters);
	}

	/**
	 * Switch "favoriteness" of a certain task for a certain user.
	 *
	 * @throws \Bitrix\Main\SystemException, TasksException
	 *
	 * @return boolean Returns true if task was not favorite, but became such, false otherwise
	 */
	public function toggleFavorite()
	{
		return $this->proceedAction(self::ACTION_TOGGLE_FAVORITE);
	}

	/**
	 * This function is deprecated, it wont work with a new disk-based file attachment mechanism.
	 * Use getAttachedFiles() instead
	 * @deprecated
	 */
	public function getFiles()
	{
		// ensure we have access to the task
		$this->checkCanReadThrowException();

		if ($this->arTaskFiles === null)
		{
			$rsFiles = CTaskFiles::GetList(
				array(),
				array('TASK_ID' => $this->taskId)
			);

			$this->arTaskFiles = array();

			while ($arFile = $rsFiles->fetch())
				$this->arTaskFiles[] = $arFile['FILE_ID'];
		}

		return ($this->arTaskFiles);
	}

	/**
	 * @param integer $fileId
	 * @throws TasksException
	 * @throws CTaskAssertException
	 *
	 * This function is deprecated, it wont work with a new disk-based file attachment mechanism
	 *
	 * @deprecated
	 */
	public function removeAttachedFile($fileId)
	{
		CTaskAssert::assertLaxIntegers($fileId);
		CTaskAssert::assert($fileId > 0);

		if ( ! $this->checkAccess(ActionDictionary::ACTION_TASK_EDIT) )
		{
			CTaskAssert::log(
				'access denied while trying to remove file: fileId=' . $fileId
				. ', taskId=' . $this->taskId . ', userId=' . $this->executiveUserId,
				CTaskAssert::ELL_WARNING
			);

			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		if ( ! CTaskFiles::Delete($this->taskId, $fileId) )
		{
			throw new TasksException(
				'File #' . $fileId . ' not attached to task #' . $this->taskId,
				TasksException::TE_FILE_NOT_ATTACHED_TO_TASK
			);
		}
	}

	/** @deprecated */
	public function isUserRole($roleId)
	{
		return false;
	}


	/**
	 * Do not use the method in case of large output expected - its too slow.
	 *
	 * @param $userId
	 * @param $arOrder
	 * @param $arFilter
	 * @param array $arParams
	 * @param array $arSelect
	 * @throws TasksException
	 * @return array $arReturn with elements
	 *        <ul>
	 *        <li>$arReturn[0] - array of items
	 *        <li>$arReturn[1] - CDBResult
	 *        </ul>
	 */
	public static function fetchList($userId, $arOrder, $arFilter, $arParams = array(), $arSelect = array())
	{
		$arItems = array();
		list($arItemsData, $rsData) = static::fetchListArray($userId, $arOrder, $arFilter, $arParams, $arSelect);

		if(is_array($arItemsData))
		{
			foreach ($arItemsData as $arItemData)
			{
				$arItems[] = self::constructWithPreloadedData($userId, $arItemData);
			}
		}

		return (array($arItems, $rsData));
	}

	/**
	 * @param int $userId
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arParams
	 * @param array $arSelect
	 * @param array $arGroup
	 * @return array
	 * @throws TasksException
	 */
	public static function fetchListArray($userId, $arOrder, $arFilter, $arParams = array(), $arSelect = array(), array $arGroup = array())
	{
		$arItemsData = array();
		$rsData = null;

		try
		{
			$arParamsOut = array(
				'USER_ID' => $userId,
				'bIgnoreErrors' => true,		// don't die on SQL errors
				'PERMISSION_CHECK_VERSION' => 2, // new sql code to check permissions
			);

			if (array_key_exists('TARGET_USER_ID', $arParams))
			{
				$arParamsOut['TARGET_USER_ID'] = $arParams['TARGET_USER_ID'];
			}
			if (isset($arParams['SORTING_GROUP_ID']))
			{
				$arParamsOut['SORTING_GROUP_ID'] = $arParams['SORTING_GROUP_ID'];
			}
			if (isset($arParams['MAKE_ACCESS_FILTER']))
			{
				$arParamsOut['MAKE_ACCESS_FILTER'] = $arParams['MAKE_ACCESS_FILTER'];
			}

			if (isset($arParams['nPageTop']))
				$arParamsOut['nPageTop'] = $arParams['nPageTop'];
			elseif (isset($arParams['NAV_PARAMS']))
				$arParamsOut['NAV_PARAMS'] = $arParams['NAV_PARAMS'];

			$arFilter['CHECK_PERMISSIONS'] = 'Y';	// Always check permissions

			if ( ! empty($arSelect) && (!isset($arParams['USE_MINIMAL_SELECT_LEGACY']) || $arParams['USE_MINIMAL_SELECT_LEGACY'] != 'N'))
			{
				$arSelect = array_merge(
					$arSelect,
					static::getMinimalSelectLegacy()
				);
			}

			$arTasksIDs  = array();
			$rsData = CTasks::getList($arOrder, $arFilter, $arSelect, $arParamsOut, $arGroup);

			if ( ! is_object($rsData) )
				throw new TasksException();

			while ($arData = $rsData->fetch())
			{
				$taskId = (int)$arData['ID'];
				$arTasksIDs[] = $taskId;

				if (in_array('AUDITORS', $arSelect) || in_array('*', $arSelect))
				{
					$arData['AUDITORS'] = [];
				}
				if (in_array('ACCOMPLICES', $arSelect) || in_array('*', $arSelect))
				{
					$arData['ACCOMPLICES'] = [];
				}

				if (array_key_exists('TITLE', $arData))
				{
					$arData['TITLE'] = \Bitrix\Main\Text\Emoji::decode($arData['TITLE']);
				}
				if (array_key_exists('DESCRIPTION', $arData) && $arData['DESCRIPTION'] !== '')
				{
					$arData['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($arData['DESCRIPTION']);
				}

				$arItemsData[$taskId]  = $arData;
			}

			if(is_array($arTasksIDs) && !empty($arTasksIDs))
			{
				if (in_array('NEW_COMMENTS_COUNT', $arSelect, true))
				{
					$newComments = Bitrix\Tasks\Internals\Counter::getInstance((int)$userId)->getCommentsCount($arTasksIDs);
					foreach ($newComments as $taskId => $commentsCount)
					{
						$arItemsData[$taskId]['NEW_COMMENTS_COUNT'] = $commentsCount;
					}
				}
				if(in_array('AUDITORS', $arSelect) || in_array('ACCOMPLICES', $arSelect) || in_array('*', $arSelect))
				{
					// fill ACCOMPLICES and AUDITORS
					$rsMembers = CTaskMembers::GetList(array(), array('TASK_ID' => $arTasksIDs));

					if (!is_object($rsMembers))
						throw new TasksException();

					while ($arMember = $rsMembers->fetch())
					{
						$taskId = (int)$arMember['TASK_ID'];

						if (in_array($taskId, $arTasksIDs, true))
						{
							if ($arMember['TYPE'] === 'A' && (in_array('ACCOMPLICES', $arSelect) || in_array('*', $arSelect)) )
								$arItemsData[$taskId]['ACCOMPLICES'][] = $arMember['USER_ID'];
							elseif ($arMember['TYPE'] === 'U' && (in_array('AUDITORS', $arSelect) || in_array('*', $arSelect)) )
								$arItemsData[$taskId]['AUDITORS'][] = $arMember['USER_ID'];
						}
					}
				}

				// fill tags
				if (isset($arParams['LOAD_TAGS']) && $arParams['LOAD_TAGS'])
				{
					foreach ($arTasksIDs as $taskId)
						$arItemsData[$taskId]['TAGS'] = array();

					$rsTags = CTaskTags::getList(array(), array('TASK_ID' => $arTasksIDs));

					if ( ! is_object($rsTags) )
						throw new TasksException();

					while ($arTag = $rsTags->fetch())
					{
						$taskId = (int) $arTag['TASK_ID'];

						if (in_array($taskId, $arTasksIDs, true))
							$arItemsData[$taskId]['TAGS'][] = $arTag['NAME'];
					}
				}

				// fill parameters
				if (isset($arParams['LOAD_PARAMETERS']))
				{
					$res = \Bitrix\Tasks\Internals\Task\ParameterTable::getList(array('filter' => array(
						'TASK_ID' => $arTasksIDs
					)));
					while($paramItem = $res->fetch())
					{
						$arItemsData[$paramItem['TASK_ID']]['SE_PARAMETER'][] = $paramItem;
					}
				}
			}
		}
		catch (Exception $e)
		{
			$message = '[0xa819f6f1] probably SQL error at ' . $e->getFile() . ':' . $e->getLine() . '. ' . $e->getMessage();
			CTaskAssert::logError($message);
			throw new TasksException(
				$e->getMessage(),
				TasksException::TE_SQL_ERROR
				| TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		return array($arItemsData, $rsData);
	}

	/**
	 * Use with caution, it simply creates an instance with data given, no rights checking!
	 */
	public static function constructWithPreloadedData($userId, $arTaskData)
	{
		$oItem = new self($arTaskData['ID'], $userId);

		if (isset($arTaskData['TAGS']))
		{
			$oItem->arTaskTags = $arTaskData['TAGS'];
			unset($arTaskData['TAGS']);
		}

		$oItem->arTaskDataEscaped = null;
		$oItem->arTaskData = $arTaskData;

		return ($oItem);
	}

	/**
	 * Reset the entire cache
	 */
	public function markCacheAsDirty($clearStaticCache = true)
	{
		$this->arTaskData           = null;
		$this->arTaskAllowedActions = null;
		$this->arTaskDataEscaped    = null;
		$this->arTaskUserRoles      = null;
		$this->arTaskFiles          = null;
		$this->arTaskTags           = null;
		$this->arTaskDependsOn      = null;
		$this->arTaskFileAttachments = null;

		$id = (int) $this->getId();

		// $this instance may not have obtained via static::getInstance(), so the code above will not take effect on
		// instances that actually have. Therefore, we need to drop each cache item for $this->getId() ALSO
		if($clearStaticCache && is_array(static::$instances))
		{
			foreach(static::$instances as $key => $instance)
			{
				$key = explode('|', $key);
				if(intval($key[0]) == $id && intval($key[1]) == $this->executiveUserId)
				{
					$instance->markCacheAsDirty(false);
				}
			}
		}
	}

	private function proceedActionEdit($arActionArguments, $arTaskData)
	{
		$this->lastOperationResultData['UPDATE'] = array();

		$arFields = $arActionArguments['FIELDS'];
		$arParams = $arActionArguments['PARAMETERS'];
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$actionChangeDeadlineFields = ['ID', 'DEADLINE', 'START_DATE_PLAN', 'END_DATE_PLAN', 'DURATION'];
		$arGivenFieldsNames = array_keys($arFields);

		$newTask = \Bitrix\Tasks\Access\Model\TaskModel::createFromArray($arFields, $arTaskData);

		if ($arGivenFieldsNames === array_intersect($arGivenFieldsNames, $actionChangeDeadlineFields))
		{
			if (!$this->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
			{
				throw new TasksException(
					GetMessage('TASKS_ACCESS_DENIED_TO_DEADLINE_UPDATE'),
					TasksException::TE_ACTION_NOT_ALLOWED
				);
			}
		}
		elseif (!$this->checkAccess(ActionDictionary::ACTION_TASK_SAVE, $newTask))
		{
			throw new TasksException(
				GetMessage('TASKS_ACCESS_DENIED_TO_TASK_UPDATE'),
				TasksException::TE_ACTION_NOT_ALLOWED
			);
		}

		if (isset($arFields['ID']))
		{
			unset($arFields['ID']);
		}

		$arParams = array_merge($arParams, array(
			'USER_ID'               => $this->executiveUserId,
			'CHECK_RIGHTS_ON_FILES' => true
		));

		$prevGroupId = (int) $arTaskData['GROUP_ID'];

		$this->checkProjectDates($arTaskData, $arFields); //

		$this->markCacheAsDirty();

		$o = new CTasks();
		/** @noinspection PhpDeprecationInspection */
		if ($o->update($this->taskId, $arFields, $arParams) !== true)
		{
			$this->markCacheAsDirty();
			static::throwExceptionVerbose($o->GetErrors());
		}
		$this->markCacheAsDirty();
		$this->lastOperationResultData['UPDATE'] = $o->getLastOperationResultData();

		if (
			($arActionArguments['SUBTASKS_CHANGE_GROUP'] ?? null) !== false
			&& array_key_exists('GROUP_ID', $arFields)
			&& $prevGroupId !== (int) $arFields['GROUP_ID']
		)
		{
			$this->moveSubTasksToGroup($arFields['GROUP_ID']);
		}

		return;
	}

	private function proceedActionFavorite($arActionArguments)
	{
		if(!is_array($arActionArguments))
			$arActionArguments = array();

		$addChildren = true;
		if(array_key_exists('AFFECT_CHILDREN', $arActionArguments))
		{
			$addChildren = $arActionArguments['AFFECT_CHILDREN'] == 'Y' || $arActionArguments['AFFECT_CHILDREN'] === true;
		}
		$tellSocnet = true;
		if(array_key_exists('TELL_SOCNET', $arActionArguments))
		{
			$tellSocnet = $arActionArguments['TELL_SOCNET'] == 'Y' || $arActionArguments['TELL_SOCNET'] === true;
		}

		$f = 'toggle';

		// ensure we have access to the task
		$this->checkCanReadThrowException();

		// drop cache
		$this->markCacheAsDirty();

		// here could be trouble: socnet doesn`t know anything aboult child tasks
		// in case of a ticket came, get all child tasks IDs here, pass ID list to \Bitrix\Tasks\Integration\Socialnetwork\Task::toggleFavorites()
		// and also pass ID list as a cache to FavoriteTable::$f to avoid calling same query twice

		$res = FavoriteTable::$f(array(
			'TASK_ID' => $this->getId(),
			'USER_ID' => $this->executiveUserId
		), array(
			'AFFECT_CHILDREN' => $addChildren
		));

		if(!$res->isSuccess())
		{
			static::throwExceptionVerbose($res->getErrors());
		}

		$result = ($res instanceof \Bitrix\Main\Entity\AddResult);
		$add = $result;

		foreach(GetModuleEvents('tasks', 'OnTaskToggleFavorite', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->getId(), $this->executiveUserId, $add));
		}

		if($tellSocnet)
		{
			\Bitrix\Tasks\Integration\Socialnetwork\Task::toggleFavorites(array(
				'TASK_ID' => $this->getId(),
				'USER_ID' => $this->executiveUserId,
				'OPERATION' => $add ? 'ADD' : 'DELETE'
			));
		}

		return $result;
	}

	private function proceedActionRemove($arActionArguments)
	{
		if (!$this->checkAccess(ActionDictionary::ACTION_TASK_REMOVE))
		{
			throw new TasksException(
				Loc::getMessage('TASKS_ACCESS_DENIED_TO_TASK_DELETE'),
				TasksException::TE_ACTION_NOT_ALLOWED | TasksException::TE_ACCESS_DENIED
			);
		}

		$this->markCacheAsDirty();

		$arParams = $arActionArguments['PARAMETERS'];

		/** @noinspection PhpDeprecationInspection */
		if (CTasks::Delete($this->taskId, $arParams) !== true)
		{
			throw new TasksException(
				'Cannot delete task '.$this->taskId,
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		return;
	}

	private function proceedAction($actionId, $arActionArguments = null)
	{
		$accessParams = null;
		$skipAccessControl = false;
		if (
			is_array($arActionArguments)
			&& isset($arActionArguments['PARAMETERS']['SKIP_ACCESS_CONTROL'])
			&& $arActionArguments['PARAMETERS']['SKIP_ACCESS_CONTROL']
		)
		{
			$skipAccessControl = true;
		}

		$actionId = ActionDictionary::getActionByLegacyId((int) $actionId);

		if (
			isset($arActionArguments['FIELDS']['RESPONSIBLE_ID'])
			&& count($arActionArguments['FIELDS']) === 1
		)
		{
			$actionId = ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE;
			$arActionArguments['RESPONSIBLE_ID'] = $arActionArguments['FIELDS']['RESPONSIBLE_ID'];
		}

		if (
			isset($arActionArguments['FIELDS']['MARK'])
			&& count($arActionArguments['FIELDS']) === 1
		)
		{
			$actionId = ActionDictionary::ACTION_TASK_RATE;
		}

		if (
			isset($arActionArguments['FIELDS']['STATUS'])
			&& count($arActionArguments['FIELDS']) === 1
		)
		{
			$actionId = ActionDictionary::ACTION_TASK_CHANGE_STATUS;
		}

		if (
			isset($arActionArguments['FIELDS']['ACCOMPLICES'])
			&& count($arActionArguments['FIELDS']) === 1
		)
		{
			$actionId = ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES;
		}

		if (
			in_array($actionId, [
				ActionDictionary::ACTION_TASK_FAVORITE,
				ActionDictionary::ACTION_TASK_FAVORITE_ADD,
				ActionDictionary::ACTION_TASK_FAVORITE_DELETE
			])
		)
		{
			return $this->proceedActionFavorite($arActionArguments);
		}

		try
		{
			$arTaskData = $this->getData($bSpecialChars = false, [], !$skipAccessControl);
		}
		catch (TasksException $e)
		{
			throw new TasksException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'), TasksException::TE_ACTION_NOT_ALLOWED | TasksException::TE_ACCESS_DENIED);
		}

		$arNewFields = null;

		if ($actionId === ActionDictionary::ACTION_TASK_REMOVE)
		{
			return $this->proceedActionRemove($arActionArguments);
		}
		elseif ($actionId === ActionDictionary::ACTION_TASK_EDIT)
		{
			return $this->proceedActionEdit($arActionArguments, $arTaskData);
		}

		switch ($actionId)
		{
			case ActionDictionary::ACTION_TASK_ACCEPT:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			case ActionDictionary::ACTION_TASK_CHANGE_STATUS:
				$arNewFields['STATUS'] = $arActionArguments['FIELDS']['STATUS'];
			break;

			case ActionDictionary::ACTION_TASK_DECLINE:
				$arNewFields['STATUS'] = CTasks::STATE_DECLINED;

				if (isset($arActionArguments['DECLINE_REASON']))
					$arNewFields['DECLINE_REASON'] = $arActionArguments['DECLINE_REASON'];
				else
					$arNewFields['DECLINE_REASON'] = '';
			break;

			case ActionDictionary::ACTION_TASK_COMPLETE:
				$isAdmin = User::isSuper($this->executiveUserId);
				$isCreator = $arTaskData['CREATED_BY'] == $this->executiveUserId;
				$isOnePersonTask = $arTaskData['CREATED_BY'] == $arTaskData['RESPONSIBLE_ID'];
				$isCreatorDirector = User::isBoss($arTaskData['CREATED_BY'], $this->executiveUserId);

				if (
					(($isAdmin || $isCreatorDirector) && $arTaskData['STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
					|| $isOnePersonTask
					|| $isCreator
					|| $arTaskData['TASK_CONTROL'] === 'N'
				)
				{
					$arNewFields['STATUS'] = CTasks::STATE_COMPLETED;
				}
				else
				{
					$arNewFields['STATUS'] = CTasks::STATE_SUPPOSEDLY_COMPLETED;
				}

				if (
					($isAdmin || $isCreator || $isCreatorDirector)
					&& $arTaskData['TASK_CONTROL'] == 'Y'
					&& $this->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
				)
				{
					$skipAccessControl = true;
				}

				break;

			case ActionDictionary::ACTION_TASK_APPROVE:
				$arNewFields['STATUS'] = CTasks::STATE_COMPLETED;
			break;

			case ActionDictionary::ACTION_TASK_START:
				$arNewFields['STATUS'] = CTasks::STATE_IN_PROGRESS;
			break;

			case ActionDictionary::ACTION_TASK_PAUSE:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			case ActionDictionary::ACTION_TASK_DELEGATE:
			case ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE:
				$newResponsibleId = $arActionArguments['RESPONSIBLE_ID'];
				$oldResponsibleId = $arTaskData['RESPONSIBLE_ID'];

				if (!isset($newResponsibleId))
				{
					throw new TasksException(
						'Expected $arActionArguments[\'RESPONSIBLE_ID\']',
						TasksException::TE_WRONG_ARGUMENTS
					);
				}

				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
				$arNewFields['RESPONSIBLE_ID'] = $newResponsibleId;

				$isScrumTask = false;
				if (Loader::includeModule('socialnetwork'))
				{
					$currentGroupId = (($arNewFields['GROUP_ID'] ?? null) > 0 ? $arNewFields['GROUP_ID'] : $arTaskData['GROUP_ID']);
					$group = Workgroup::getById($currentGroupId);
					$isScrumTask = ($group && $group->isScrumProject());
				}

				if (isset($arTaskData['AUDITORS']) && count($arTaskData['AUDITORS']))
				{
					if (!in_array($oldResponsibleId, $arTaskData['AUDITORS']))
					{
						$arNewFields['AUDITORS'] = $arTaskData['AUDITORS'];
						if (!$isScrumTask)
						{
							$arNewFields['AUDITORS'][] = $oldResponsibleId;
						}
					}
				}
				else
				{
					if (!$isScrumTask)
					{
						$arNewFields['AUDITORS'] = [$oldResponsibleId];
					}
				}

				$accessParams = \Bitrix\Tasks\Access\Model\TaskModel::createFromArray($arNewFields, $arTaskData);

			break;

			case ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES:
				if (!$arNewFields || !array_key_exists('ACCOMPLICES', $arNewFields))
				{
					$arNewFields['ACCOMPLICES'] = [];
				}

				foreach ($arActionArguments['FIELDS']['ACCOMPLICES'] as $acc)
				{
					$arNewFields['ACCOMPLICES'][] = $acc;
				}

				$accessParams = \Bitrix\Tasks\Access\Model\TaskModel::createFromArray($arNewFields, $arTaskData);

			break;

			case ActionDictionary::ACTION_TASK_RATE:
				$arNewFields['MARK'] = $arActionArguments['FIELDS']['MARK'];
			break;

			case ActionDictionary::ACTION_TASK_DEFER:
				$arNewFields['STATUS'] = CTasks::STATE_DEFERRED;
			break;

			case ActionDictionary::ACTION_TASK_DISAPPROVE:
			case ActionDictionary::ACTION_TASK_RENEW:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			default:
			break;
		}

		if ($arNewFields === null)
		{
			throw new TasksException();
		}

		// Don't update task, if nothing changed
		$bNeedUpdate = false;

		foreach ($arNewFields as $fieldName => $newValue)
		{
			$curValue = $arTaskData[$fieldName];

			// Convert task data arrays to strings, for comparing
			if (is_array($curValue))
			{
				sort($curValue);
				sort($newValue);
				$curValue = implode('|', $curValue);
				$newValue = implode('|', $newValue);
			}

			if ($curValue != $newValue)
			{
				$bNeedUpdate = true;
				break;
			}
		}

		if ($bNeedUpdate)
		{
			if (!$skipAccessControl && !$this->checkAccess($actionId, $accessParams))
			{
				throw new TasksException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'), TasksException::TE_ACTION_NOT_ALLOWED | TasksException::TE_ACCESS_DENIED);
			}

			$arParams = array_merge($arActionArguments['PARAMETERS'], ['USER_ID' => $this->executiveUserId]);
			$o = new CTasks();
			/** @noinspection PhpDeprecationInspection */

			$this->markCacheAsDirty(); // for ->getData() called inside onAfterUpdate inside CTasks::Update()
			if ($o->Update($this->taskId, $arNewFields, $arParams) !== true)
			{
				static::throwExceptionVerbose($o->GetErrors());
			}
			$this->markCacheAsDirty(); // for the rest of the code below
		}
	}

	private function checkProjectDates($arTaskData, $arNewFields)
	{
		if (array_key_exists('GROUP_ID', $arNewFields) && (int)$arNewFields['GROUP_ID'] > 0)
		{
			$groupId = (int)$arNewFields['GROUP_ID'];
		}
		else
		{
			$groupId = (int)$arTaskData['GROUP_ID'];
		}

		if (!$groupId)
		{
			return;
		}

		if (\CModule::includeModule('socialnetwork'))
		{
			$group = \CSocNetGroup::getById($groupId);

			if ($group && $group['PROJECT'] == 'Y' && ($group['PROJECT_DATE_START'] || $group['PROJECT_DATE_FINISH']))
			{
				$projectStartDate = DateTime::createFrom($group['PROJECT_DATE_START']);
				$projectFinishDate = DateTime::createFrom($group['PROJECT_DATE_FINISH']);

				if ($projectFinishDate)
				{
					$projectFinishDate->addSecond(86399); // + 23:59:59
				}

				$deadline = DateTime::createFrom($arTaskData['DEADLINE']);
				$endDatePlan = DateTime::createFrom($arTaskData['END_DATE_PLAN']);
				$startDatePlan = DateTime::createFrom($arTaskData['START_DATE_PLAN']);

				if (isset($arNewFields['DEADLINE']) && $arNewFields['DEADLINE'])
				{
					$deadline = DateTime::createFrom($arNewFields['DEADLINE']);
				}
				if (isset($arNewFields['END_DATE_PLAN']) && $arNewFields['END_DATE_PLAN'])
				{
					$endDatePlan = DateTime::createFrom($arNewFields['END_DATE_PLAN']);
				}
				if (isset($arNewFields['START_DATE_PLAN']) && $arNewFields['START_DATE_PLAN'])
				{
					$startDatePlan = DateTime::createFrom($arNewFields['START_DATE_PLAN']);
				}

				if ($deadline && !$deadline->checkInRange($projectStartDate, $projectFinishDate))
				{
					$this->_errors[] = ["text" => GetMessage("TASKS_DEADLINE_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
				}

				if ($endDatePlan && !$endDatePlan->checkInRange($projectStartDate, $projectFinishDate))
				{
					$this->_errors[] = ["text" => GetMessage("TASKS_PLAN_DATE_END_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
				}

				if ($startDatePlan && !$startDatePlan->checkInRange($projectStartDate, $projectFinishDate))
				{
					$this->_errors[] = ["text" => GetMessage("TASKS_PLAN_DATE_START_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
				}

				if (!empty($this->_errors))
				{
					static::throwExceptionVerbose($this->_errors);
				}
			}
		}
	}

	private static $cacheAFWasDisabled = false;
	private static $notifADWasDisabled = false;

	private static function enableUpdateBatchMode()
	{
		self::$cacheAFWasDisabled = CTasks::disableCacheAutoClear();
		self::$notifADWasDisabled = CTaskNotifications::disableAutoDeliver();
	}

	private static function disableUpdateBatchMode()
	{
		if(self::$notifADWasDisabled)
		{
			CTaskNotifications::enableAutoDeliver();
		}
		if(self::$cacheAFWasDisabled)
		{
			CTasks::enableCacheAutoClear();
		}
	}

	private function moveSubTasksToGroup($groupId)
	{
		static::enableUpdateBatchMode();

		$subTasks = CTasks::getTaskSubTree($this->taskId);
		foreach($subTasks as $sTaskId)
		{
			try
			{
				$sub = new CTaskItem($sTaskId, $this->executiveUserId);
				$sub->update(array('GROUP_ID' => $groupId), array('SUBTASKS_CHANGE_GROUP' => false));
			}
			catch(TasksException | CTaskAssertException $e)
			{
				static::disableUpdateBatchMode();

				if(!$e->checkIsActionNotAllowed())
				{
					throw $e;
				}
			}
		}

		static::disableUpdateBatchMode();
	}

	private static function getSubEmployees($userId)
	{
		static $subEmployeesCache = [];

		if (!isset($subEmployeesCache[$userId]))
		{
			$subEmployeesCache[$userId] = Integration\Intranet\User::getSubordinateSubDepartments($userId);
		}

		return $subEmployeesCache[$userId];
	}

	private static function getUserRolesArray($userId, array $taskData)
	{
		$userRole = 0;

		if (isset($taskData['CREATED_BY']) && (int)$taskData['CREATED_BY'] === $userId)
		{
			$userRole |= self::ROLE_DIRECTOR;
		}

		if (isset($taskData['RESPONSIBLE_ID']) && (int)$taskData['RESPONSIBLE_ID'] === $userId)
		{
			$userRole |= self::ROLE_RESPONSIBLE;
		}

		if (
			array_key_exists('ACCOMPLICES', $taskData)
			&& is_array($taskData['ACCOMPLICES'])
			&& in_array($userId, $taskData['ACCOMPLICES'])
		)
		{
			$userRole |= self::ROLE_ACCOMPLICE;
		}

		if (
			array_key_exists('AUDITORS', $taskData)
			&& is_array($taskData['AUDITORS'])
			&& in_array($userId, $taskData['AUDITORS'])
		)
		{
			$userRole |= self::ROLE_AUDITOR;
		}

		// Now, process subordinated users
		$allRoles = self::ROLE_DIRECTOR | self::ROLE_RESPONSIBLE | self::ROLE_ACCOMPLICE | self::ROLE_AUDITOR;

		if ($userRole !== $allRoles)
		{
			$subEmployees = static::getSubEmployees($userId);

			if (!empty($subEmployees))
			{
				// Check only roles, that user doesn't have already
				if (
					!($userRole & self::ROLE_DIRECTOR)
					&& array_key_exists('CREATED_BY', $taskData)
					&& in_array($taskData['CREATED_BY'], $subEmployees, true)
				)
				{
					$userRole |= self::ROLE_DIRECTOR;
				}

				if (
					!($userRole & self::ROLE_RESPONSIBLE)
					&& array_key_exists('RESPONSIBLE_ID', $taskData)
					&& in_array($taskData['RESPONSIBLE_ID'], $subEmployees, true)
				)
				{
					$userRole |= self::ROLE_RESPONSIBLE;
				}

				if (
					!($userRole & self::ROLE_ACCOMPLICE)
					&& array_key_exists('ACCOMPLICES', $taskData)
					&& is_array($taskData['ACCOMPLICES'])
				)
				{
					foreach ($taskData['ACCOMPLICES'] as $accompliceId)
					{
						if (in_array($accompliceId, $subEmployees, true))
						{
							$userRole |= self::ROLE_ACCOMPLICE;
							break;
						}
					}
				}

				if (
					!($userRole & self::ROLE_AUDITOR)
					&& array_key_exists('AUDITORS', $taskData)
					&& is_array($taskData['AUDITORS'])
				)
				{
					foreach ($taskData['AUDITORS'] as $auditorId)
					{
						if (in_array($auditorId, $subEmployees, true))
						{
							$userRole |= self::ROLE_AUDITOR;
							break;
						}
					}
				}
			}
		}

		// No role in task?
		if ($userRole === 0)
		{
			$userRole = self::ROLE_NOT_A_MEMBER;
		}

		return $userRole;
	}

	private function getUserRoles()
	{
		try
		{
			$arTask = $this->getData($bEscaped = false);
		}
		catch (TasksException $e)
		{
			return [];
		}

		$userId = $this->executiveUserId;

		// Is there precached data?
		if ($this->arTaskUserRoles === null)
		{
			$this->arTaskUserRoles = self::getUserRolesArray($userId, $arTask);
		}

		return ($this->arTaskUserRoles);
	}

	private function throwExceptionNotAccessible()
	{
		throw new TasksException('Task not found or not accessible', TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE);
	}

	private static function throwExceptionVerbose($errorDescription = array(), $additionalFlags = 0)
	{
		throw new TasksException(
			serialize($errorDescription),
			TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			| $additionalFlags
		);
	}

	// task dependence mechanism

	/**
	 * Get id of tasks that current task depends on
	 *
	 * @deprecated Use manager instead
	 */
	public function getDependsOn()
	{
		// ensure we have access to the task
		$this->checkCanReadThrowException();

		if ($this->arTaskDependsOn === null)
		{
			$rsDependsOn = CTaskDependence::GetList(
				array(),
				array('TASK_ID' => $this->taskId)
			);

			$arTaskDependsOn = array();

			while ($arDependsOn = $rsDependsOn->fetch())
				$arTaskDependsOn[] = $arDependsOn['DEPENDS_ON_ID'];

			$this->arTaskDependsOn = $arTaskDependsOn;
		}

		return ($this->arTaskDependsOn);
	}

	public function addProjectDependence($parentId, $linkType = DependenceTable::LINK_TYPE_FINISH_START)
	{
		$exceptionInfo = array(
			'AUX' => array(
				'MESSAGE' => array(
					'FROM_TASK_ID' => $parentId,
					'TASK_ID' => $this->getId(),
					'LINK_TYPE' => $linkType
				)
			)
		);

		if(!\Bitrix\Tasks\Util\Restriction::checkCanCreateDependence())
		{
			$exceptionInfo['ERROR'] = array(
				array(
					'CODE' => 'TRIAL_EXPIRED',
					'MESSAGE' => Loc::getMessage('TASKS_TRIAL_PERIOD_EXPIRED')
				)
			);

			throw new ActionRestrictedException(Loc::getMessage('TASK_CANT_ADD_LINK'), $exceptionInfo);
		}

		if($this->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
		{
			$parentTask = CTaskItem::getInstanceFromPool($parentId, $this->executiveUserId);
			if($parentTask->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
			{
				// DependenceTable does not care about PARENT_ID relations and other restrictions except
				// those which may compromise dependence mechanism logic
				// so PARENT_ID check and DATE assignment are placed outside

				$errors = array();
				if($this->checkIsSubtaskOf($parentId) || $parentTask->checkIsSubtaskOf($this->getId()))
				{
					$errors['TASKS_HAS_PARENT_RELATION'] = Loc::getMessage('TASKS_HAS_PARENT_RELATION');
				}
				else
				{
					$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->executiveUserId);

					try
					{
						$scheduler->defineTaskDates($this->getData())->save();
						$scheduler->defineTaskDates($parentTask->getData())->save();
					}
					catch (TasksException $e)
					{
						throw new ActionFailedException(Loc::getMessage('TASK_CANT_ADD_LINK'), $exceptionInfo);
					}

					$result = DependenceTable::createLink($this->getId(), $parentId, array(
						//'TASK_DATA' => 			$this->getData(false),
						//'PARENT_TASK_DATA' => 	$parentTask->getData(false),
						'LINK_TYPE' => $linkType,
						'CREATOR_ID' => $this->executiveUserId
					));
					if(!$result->isSuccess())
					{
						$errors = array_merge($errors, $result->getErrorMessages());
					}
				}

				if(!empty($errors))
				{
					$exceptionInfo['ERROR'] = $errors;
					throw new ActionFailedException(Loc::getMessage('TASK_CANT_ADD_LINK'), $exceptionInfo);
				}

				return;
			}
		}

		throw new ActionNotAllowedException(Loc::getMessage('TASK_CANT_ADD_LINK'), $exceptionInfo);
	}

	public function checkIsSubtaskOf($taskId)
	{
		$met = array();
		$exitLimit = 1000;

		// recursive queries, no tree structure here

		$task = $this->getId();
		$met[$task] = true;
		$i = 0;
		while(true)
		{
			if($i === 0)
			{
				$parent = $this['PARENT_ID'];
			}
			else
			{
				$parent = CTasks::getParentOfTask($task);
			}

			if(isset($met[$parent])) // chain is loopy
			{
				return false;
			}
			if($i > $exitLimit) // smth is too wrong
			{
				return false;
			}

			if($parent === false || !intval($parent)) // no parent anymore
			{
				return false;
			}

			if($parent == $taskId) // found
			{
				return true;
			}

			$met[$parent] = true;
			$task = $parent;
			$i++;
		}

		return false;
	}

	public function updateProjectDependence($parentId, $linkType = DependenceTable::LINK_TYPE_FINISH_START)
	{
		$exceptionInfo = array(
			'AUX' => array(
				'MESSAGE' => array(
					'FROM_TASK_ID' => $parentId,
					'TASK_ID' => $this->getId(),
					'LINK_TYPE' => $linkType
				)
			)
		);

		if($this->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
		{
			$parentTask = CTaskItem::getInstanceFromPool($parentId, $this->executiveUserId);
			if($parentTask->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
			{
				$result = DependenceTable::update(array(
					'TASK_ID' => $this->getId(),
					'DEPENDS_ON_ID' => $parentId,
				), array(
					'TYPE' => $linkType
				));
				if(!$result->isSuccess())
				{
					$exceptionInfo['ERROR'] = $result->getErrorMessages();
					throw new ActionFailedException(Loc::getMessage('TASK_CANT_UPDATE_LINK'), $exceptionInfo);
				}
				return;
			}
		}

		throw new ActionNotAllowedException(Loc::getMessage('TASK_CANT_UPDATE_LINK'), $exceptionInfo);
	}

	public function deleteProjectDependence($parentId)
	{
		$exceptionInfo = array(
			'AUX' => array(
				'MESSAGE' => array(
					'FROM_TASK_ID' => $parentId,
					'TASK_ID' => $this->getId(),
					//'LINK_TYPE' => $linkType
				)
			)
		);

		if($this->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
		{
			$parentTask = CTaskItem::getInstanceFromPool($parentId, $this->executiveUserId);
			if($parentTask->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
			{
				$result = DependenceTable::deleteLink($this->getId(), $parentId);
				if(!$result->isSuccess())
				{
					$exceptionInfo['ERROR'] = $result->getErrorMessages();
					throw new ActionFailedException(Loc::getMessage('TASK_CANT_DELETE_LINK'), $exceptionInfo);
				}
				return;
			}
		}

		throw new ActionNotAllowedException(Loc::getMessage('TASK_CANT_DELETE_LINK'), $exceptionInfo);
	}

	############################################################################################
	### Everything below consider only REST interface, and should not be used as server-side API
	############################################################################################

	/**
	 * Do some post-processing of result of calling particular methods.
	 * This method is only for rest purposes
	 *
	 * @access private
	 */
	public static function postProcessRestRequest($methodName, $result, $parameters = array())
	{
		if(!is_array($parameters))
		{
			$parameters = array();
		}

		$originResult = $result;

		if($methodName == 'getfiles')
		{
			$result = array('UF_TASK_WEBDAV_FILES' => $result);

			// translate file UF values
			$result = UserField::postProcessValues($result, array(
				'FIELDS' => static::getEntityUserFields(),
				'SERVER' => $parameters['SERVER']
			));
			$result = $result['UF_TASK_WEBDAV_FILES'];
		}

		if($methodName == 'addfile')
		{
			if(intval($result))
			{
				$result = array('UF_TASK_WEBDAV_FILES' => array($result));

				// translate file UF values
				$result = UserField::postProcessValues($result, array(
					'FIELDS' => static::getEntityUserFields(),
					'SERVER' => $parameters['SERVER']
				));
				if(isset($result['UF_TASK_WEBDAV_FILES'][0]))
				{
					$result = $result['UF_TASK_WEBDAV_FILES'][0];
				}
				else
				{
					return $originResult;
				}
			}
		}

		if($methodName == 'getdata')
		{
			// CTaskItem::getData() does not return tags, but we want them in rest
			if(!empty($result) && intval($result['ID']))
			{
				$result['TAGS'] = array();
				// at this point we know we already have access to this task, so no rights check needed. use simple get list here
				$res = CTaskTags::GetList(array(), array('TASK_ID' => $result['ID']));
				while($item = $res->fetch())
				{
					$result['TAGS'][] = $item['NAME'];
				}
			}

			// translate file UF values
			$result = UserField::postProcessValues($result, array(
				'FIELDS' => static::getEntityUserFields(),
				'SERVER' => $parameters['SERVER']
			));
		}

		return $result;
	}

	/**
	 * This method is only for rest purposes
	 *
	 * @access private
	 */
	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		$rsData = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskitem', $methodName, $args);

		$runAs = $methodName;
		if(isset($arMethodsMetaInfo[$methodName]['runAs']) && (string) $arMethodsMetaInfo[$methodName]['runAs'] != '')
		{
			$runAs = $arMethodsMetaInfo[$methodName]['runAs'];
		}

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$argsParsed[] = $executiveUserId;
				/** @var CTaskItem $oTaskItem */
				$oTaskItem    = call_user_func_array(array('self', $methodName), $argsParsed);
				$taskId       = (int) $oTaskItem->getId();
				$returnValue  = $taskId;
				self::cacheInstanceInPool($taskId, $executiveUserId, $oTaskItem);
			}
			elseif ($methodName === 'getlist' || $methodName === 'list') // todo: temporal fix
			{
				array_unshift($argsParsed, $executiveUserId);

				// we need to fill default values up to $arParams (4th) argument
				while (!array_key_exists(3, $argsParsed))
				{
					$argsParsed[] = [];
				}

				if ($navigation['iNumPage'] > 1)
				{
					$argsParsed[3]['NAV_PARAMS'] = [
						'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
						'iNumPage' => (int)$navigation['iNumPage'],
					];
				}
				else if (isset($argsParsed[3]['NAV_PARAMS']))
				{
					if (isset($argsParsed[3]['NAV_PARAMS']['nPageTop']))
					{
						$argsParsed[3]['NAV_PARAMS']['nPageTop'] = min(
							CTaskRestService::TASKS_LIMIT_TOP_COUNT,
							(int)$argsParsed[3]['NAV_PARAMS']['nPageTop']
						);
					}
					if (isset($argsParsed[3]['NAV_PARAMS']['nPageSize']))
					{
						$argsParsed[3]['NAV_PARAMS']['nPageSize'] = min(
							CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
							(int)$argsParsed[3]['NAV_PARAMS']['nPageSize']
						);
					}
					if (
						!isset($argsParsed[3]['NAV_PARAMS']['nPageTop'])
						&& !isset($argsParsed[3]['NAV_PARAMS']['nPageSize'])
					)
					{
						$argsParsed[3]['NAV_PARAMS'] = [
							'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
							'iNumPage' => 1,
						];
					}
				}
				else
				{
					$argsParsed[3]['NAV_PARAMS'] = [
						'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
						'iNumPage' => 1,
					];
				}
				$argsParsed[3]['NAV_PARAMS']['getTotalCount'] = true;

				$filter = $argsParsed[2];
				$allowedParentIdNullValues = ['0', 'NULL', 'null'];

				if (array_key_exists('PARENT_ID', $filter) && in_array($filter['PARENT_ID'], $allowedParentIdNullValues))
				{
					$argsParsed[2]['PARENT_ID'] = false;
				}

				/** @var CTaskItem[] $oTaskItems */
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($oTaskItems, $rsData) = call_user_func_array(array('self', 'fetchList'), $argsParsed);

				$returnValue = array();

				foreach ($oTaskItems as $oTaskItem)
				{
					$arTaskData = $oTaskItem->getData(false);
					$arTaskData['ALLOWED_ACTIONS'] = $oTaskItem->getAllowedActionsAsStrings();

					if (isset($argsParsed[3]))
					{
						if (isset($argsParsed[3]['LOAD_TAGS']) && ($argsParsed[3]['LOAD_TAGS'] == 1 || $argsParsed[3]['LOAD_TAGS'] == 'Y'))
						{
							$arTaskData['TAGS'] = $oTaskItem->getTags();
						}
					}

					$returnValue[] = $arTaskData;
				}
			}
			else
			{
				$returnValue = call_user_func_array(array('self', $runAs), $argsParsed);
			}
		}
		else
		{
			$taskId = array_shift($argsParsed);

			if($runAs == 'isactionallowed') // modify isactionallowed() behaviour
			{
				$actionId = $argsParsed[0];
				if(intval($argsParsed[1]))
				{
					$executiveUserId = intval($argsParsed[1]);
				}

				$oTask  = self::getInstanceFromPool($taskId, $executiveUserId);

				if($actionId == self::ACTION_READ)
				{
					$returnValue = true;
					try
					{
						$oTask->getData(false);
					}
					catch(\Exception $e)
					{
						$returnValue = false;
					}
				}
				else
				{
					$returnValue = call_user_func_array(array($oTask, $runAs), array($actionId));
				}
			}
			else
			{
				$oTask  = self::getInstanceFromPool($taskId, $executiveUserId);
				$returnValue = call_user_func_array(array($oTask, $runAs), $argsParsed);
			}
		}

		return (array($returnValue, $rsData));
	}

	/**
	 * Just returns value of UF_TASK_WEBDAV_FILES field.
	 * This method is only for rest purposes.
	 *
	 * @access private
	 *
	 * @return string[]
	 */
	public function getAttachmentIds()
	{
		// ensure we have access to the task
		$this->checkCanReadThrowException();

		if ($this->arTaskFileAttachments === null)
		{
			list($items, $res) = static::fetchList($this->executiveUserId, array(), array('ID' => $this->taskId), array(), array('ID', 'UF_TASK_WEBDAV_FILES'));

			$this->arTaskFileAttachments = array();
			if(isset($items[0]))
			{
				$data = $items[0]->getData();
				if(is_array($data['UF_TASK_WEBDAV_FILES']))
				{
					$this->arTaskFileAttachments = $data['UF_TASK_WEBDAV_FILES'];
				}
			}
		}

		return ($this->arTaskFileAttachments);
	}

	/**
	 * This method is only for rest purposes
	 *
	 * @access private
	 */
	public function addFile(array $fileParameters)
	{
		if ( ! $this->checkAccess(ActionDictionary::ACTION_TASK_EDIT) )
		{
			throw new TasksException('Access denied', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$attachmentId = (int) Attachment::add($this->getId(), $fileParameters, array(
			'USER_ID' => $GLOBALS['USER']->GetId(),
			'ENTITY_ID' => UserField::getTargetEntityId(),
			'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES'
		));

		// drop cache
		$this->markCacheAsDirty();

		return $attachmentId;
	}

	/**
	 * This method is only for rest purposes
	 */
	public function deleteFile($attachmentId)
	{
		Attachment::delete($this->getId(), $attachmentId, array(
			'USER_ID' => $GLOBALS['USER']->GetId(),
			'ENTITY_ID' => UserField::getTargetEntityId(),
			'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES'
		));

		// drop cache
		$this->markCacheAsDirty();
	}

	protected static function getEntityUserFields()
	{
		static $entityUserFields;

		if($entityUserFields === null)
		{
			$res = UserField::getFieldList();
			while($item = $res->fetch())
			{
				$entityUserFields[$item['FIELD_NAME']] = $item;
			}
		}

		return $entityUserFields;
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 *
	 * @access private
	 */
	public static function getManifest()
	{
		static $arWritableTaskDataKeys = null;
		static $arReadableTaskDataKeys = null;
		static $arFilterableTaskDataKeys = null;
		static $arDateKeys             = null;
		static $arSortableTaskDataKeys = null;

		if ($arReadableTaskDataKeys === null)
		{
			$arCTasksManifest = CTasks::getManifest();

			$arSortableTaskDataKeys = 	$arCTasksManifest['REST: sortable task data fields'];
			$arFilterableTaskDataKeys = $arCTasksManifest['REST: filterable task data fields'];
			$arDateKeys = 				$arCTasksManifest['REST: date fields'];

			// mix up user fields, only reading, writing and selecting are supported for them
			$userFields = array_keys(static::getEntityUserFields());
			if(!empty($userFields))
			{
				$arWritableTaskDataKeys = 		array_merge($arCTasksManifest['REST: writable task data fields'], $userFields);
				$arReadableTaskDataKeys = 		array_merge($arCTasksManifest['REST: readable task data fields'], $userFields);
			}
		}

		$listMethodData = array(
			'staticMethod'         =>  true,
			'mandatoryParamsCount' =>  0,
			'params' => array(
				array(
					'description' => 'arOrder',
					'type'        => 'array',
					'allowedKeys' =>  $arSortableTaskDataKeys,
				),
				array(
					'description' => 'arFilter',
					'type'        => 'array',
					'allowedKeys' =>  $arFilterableTaskDataKeys,
					'allowedKeyPrefixes' => array(
						'=', '!=', '%', '!%', '?', '><',
						'!><', '>=', '>', '<', '<=', '!'
					)
				),
				array(
					'description' => 'arParams',
					'type'        => 'array',
					'allowedKeys' =>  array('NAV_PARAMS', 'LOAD_TAGS')
				),
				array(
					'description' => 'arSelect',
					'type'        => 'array',
				)
			),
			'allowedKeysInReturnValue' => array_merge(
				$arReadableTaskDataKeys,
				array('ALLOWED_ACTIONS', 'TAGS')
			),
			'collectionInReturnValue'  => true
		);

		$favoriteParameters = array('AFFECT_CHILDREN');

		return(array(
			'Manifest version' => '1.2',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class'    => 'item',
			'REST: writable task data fields'   =>  $arWritableTaskDataKeys,
			'REST: readable task data fields'   =>  $arReadableTaskDataKeys,
			'REST: filterable task data fields' =>  $arFilterableTaskDataKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),

				'getlist' => $listMethodData, // temporal fix: implement method aliasing later
				'list' => $listMethodData, // temporal fix: implement method aliasing later
				'add' => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'arNewTaskData',
							'type'        => 'array',
							'allowedKeys' => $arWritableTaskDataKeys
						)
					)
				),
				'getexecutiveuserid' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getdata' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description'  => 'isReturnEscapedData',
							'type'         => 'boolean',
							'defaultValue' =>  false
						)
					),
					'allowedKeysInReturnValue' => $arReadableTaskDataKeys
				),
				'getdescription' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'format',
							'type'        => 'integer'
						)
					)
				),
				'getfiles' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					),
					'runAs' => 'getattachmentids'
				),
				'addfile' => array(
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'fileParameters',
							'type'        => 'array',
							'allowedKeys' => array('NAME', 'CONTENT')
						)
					),
				),
				'deletefile' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'fileId',
							'type'        => 'integer'
						),
					),
				),
				'gettags' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getdependson' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getallowedtaskactions' => array(
					'alias'                => 'getallowedactions',
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getallowedtaskactionsasstrings' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						),
						array(
							'description' => 'userId',
							'type'        => 'integer'
						)
					)
				),
				'delete' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'delegate' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'userId',
							'type'        => 'integer'
						)
					)
				),
				'startexecution' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'pauseexecution' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'defer' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'complete' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'update' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arNewTaskData',
							'type'        => 'array',
							'allowedKeys' => $arWritableTaskDataKeys
						)
					)
				),
				'renew' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'approve' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'disapprove' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'addtofavorite' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'parameters',
							'type'        => 'array',
							'allowedKeys' => $favoriteParameters
						)
					)
				),
				'deletefromfavorite' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'parameters',
							'type'        => 'array',
							'allowedKeys' => $favoriteParameters
						)
					)
				)
			),
		));
	}

	//////////////////////////
	// Compatibility functions
	//////////////////////////

	// ORM and non-ORM entity columns doesnt match. To fix this, use the compatibility functions

	public static function getMinimalSelect($legacy = true)
	{
		$select = array('ID', 'RESPONSIBLE_ID', 'CREATED_BY', 'GROUP_ID', 'STATUS');

		if($legacy)
		{
			return array_merge($select, array('REAL_STATUS'));
		}
		else
		{
			return array_merge($select, array('STATUS_PSEUDO'));
		}

		return $select;
	}

	public static function getMinimalSelectLegacy()
	{
		return static::getMinimalSelect();
	}
	public static function getMinimalSelectORM()
	{
		return static::getMinimalSelect(false);
	}

	///////////////////////////////
	// Dependency support functionality (GanttTask port)
	///////////////////////////////

	protected 	$calendar = 			null;
	private 	$startDatePlanGmt = 	null;
	private 	$endDatePlanGmt = 		null;

	/**
	 * Set calendar object to use
	 */
	public function setCalendar(Calendar $calendar)
	{
		$this->calendar = $calendar;
	}

	protected function initializeCalendar()
	{
		if($this->calendar === null)
		{
			$this->calendar = new Calendar();
		}
	}

	/**
	 * Update task START_DATE_PLAN field of the instance
	 */
	public function setStartDatePlan(DateTime $date)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return;
		}

		$this->arTaskData['START_DATE_PLAN'] = $date;
		$this->startDatePlanGmt = null;
	}

	/**
	 * Update task END_DATE_PLAN field of the instance
	 */
	public function setEndDatePlan(DateTime $date)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return;
		}

		$this->arTaskData['END_DATE_PLAN'] = $date;
		$this->endDatePlanGmt = null;
	}

	/**
	 * Get task START_DATE_PLAN field of the instance
	 *
	 * @param boolean $getCreatedDateOnNull if set to true, START_DATE_PLAN is empty and CREATED_DATE is not empty, the last will be returned instead of real START_DATE_PLAN
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getStartDatePlan($getCreatedDateOnNull = false)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return null;
		}

		$date = $this->getStartDateOrCreatedDate($getCreatedDateOnNull);

		if(is_string($date) && !empty($date))
		{
			// $date containst user localtime
			return DateTime::createFromUserTime($date);
		}
		elseif($date instanceof DateTime)
		{
			return clone $date;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get task END_DATE_PLAN field of the instance
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getEndDatePlan()
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return null;
		}

		if(is_string($this->arTaskData['END_DATE_PLAN']) && !empty($this->arTaskData['END_DATE_PLAN']))
		{
			return DateTime::createFromUserTime($this->arTaskData['END_DATE_PLAN']);
		}
		elseif($this->arTaskData['END_DATE_PLAN'] instanceof DateTime)
		{
			return clone $this->arTaskData['END_DATE_PLAN'];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get (not "convert") task START_DATE_PLAN field of the instance as (not "in") GMT
	 *
	 * @param boolean $getCreatedDateOnNull if set to true, START_DATE_PLAN is empty and CREATED_DATE is not empty, the last will be returned instead of real START_DATE_PLAN
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getStartDatePlanGmt($getCreatedDateOnNull = false)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return null;
		}

		$date = $this->getStartDateOrCreatedDate($getCreatedDateOnNull);

		if((string) $date == '')
		{
			return null;
		}

		if($this->startDatePlanGmt === null)
		{
			if($date instanceof DateTime)
			{
				$date = $date->toStringGmt();
			}

			$this->startDatePlanGmt = DateTime::createFromUserTimeGmt($date); // string or object allowed here
		}

		return clone $this->startDatePlanGmt;
	}

	/**
	 * Get (not "convert") task END_DATE_PLAN field of the instance as (not "in") GMT
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getEndDatePlanGmt()
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return null;
		}

		if((string) $this->arTaskData['END_DATE_PLAN'] == '')
		{
			return null;
		}

		if($this->endDatePlanGmt === null)
		{
			$date = $this->arTaskData['END_DATE_PLAN'];

			if($date instanceof DateTime)
			{
				$date = $date->toStringGmt();
			}

			$this->endDatePlanGmt = DateTime::createFromUserTimeGmt($date); // string or object allowed here
		}

		return clone $this->endDatePlanGmt;
	}

	/**
	 * Set task START_DATE_PLAN from user time string as GMT
	 *
	 * @param string $timeString Datetime that treated as GMT
	 * @return void
	 */
	public function setStartDatePlanUserTimeGmt($timeString)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return;
		}

		if((string) $timeString == '')
		{
			$this->startDatePlanGmt = null;
			$this->arTaskData['START_DATE_PLAN'] = null;
		}
		else
		{
			$this->startDatePlanGmt = DateTime::createFromUserTimeGmt($timeString);
			$this->arTaskData['START_DATE_PLAN'] = DateTime::createFromUserTime($timeString);
		}
	}

	/**
	 * Set task END_DATE_PLAN from user time string as GMT
	 *
	 * @param string $timeString Datetime that treated as GMT
	 * @return void
	 */
	public function setEndDatePlanUserTimeGmt($timeString)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return;
		}

		if((string) $timeString == '')
		{
			$this->endDatePlanGmt = null;
			$this->arTaskData['END_DATE_PLAN'] = null;
		}
		else
		{
			$this->endDatePlanGmt = DateTime::createFromUserTimeGmt($timeString);
			$this->arTaskData['END_DATE_PLAN'] = DateTime::createFromUserTime($timeString);
		}
	}

	/**
	 * Update task MATCH_WORK_TIME field of the instance
	 * @param string $flag
	 * @return void
	 */
	public function setMatchWorkTime($flag)
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return;
		}

		$this->arTaskData['MATCH_WORK_TIME'] = $flag ? 'Y' : 'N';
	}

	public function getMatchWorkTime()
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return false;
		}

		return $this->arTaskData['MATCH_WORK_TIME'] == 'Y';
	}

	public function getDurationType()
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return CTasks::TIME_UNIT_TYPE_HOUR;
		}

		if((string) $this->arTaskData['DURATION_TYPE'] == '' || !in_array($this->arTaskData['DURATION_TYPE'], array(
			CTasks::TIME_UNIT_TYPE_DAY,
			CTasks::TIME_UNIT_TYPE_HOUR,
			CTasks::TIME_UNIT_TYPE_MINUTE
		)))
		{
			return CTasks::TIME_UNIT_TYPE_HOUR;
		}

		return $this->arTaskData['DURATION_TYPE'];
	}

	/**
	 * Calculate task duration according to current START_DATE_PLAN, END_DATE_PLAN, MATCH_WORK_TIME and Calendar settings
	 * @return integer
	 */
	public function calculateDuration()
	{
		try
		{
			$this->ensureDataLoaded();
		}
		catch (TasksException $e)
		{
			return 0;
		}

		if(!$this->getEndDatePlan()) // limitless task
		{
			return 0;
		}

		if ($this->arTaskData['MATCH_WORK_TIME'] == 'Y')
		{
			$this->initializeCalendar();

			$duration = $this->calendar->calculateDuration($this->getStartDatePlanGmt(true), $this->getEndDatePlanGmt());
			return ($duration > 0 ? $duration : $this->getEndDatePlanGmt()->getTimestamp() - $this->getStartDatePlanGmt(true)->getTimestamp());
		}
		else
		{
			return ($this->getEndDatePlanGmt()->getTimestamp() - $this->getStartDatePlanGmt(true)->getTimestamp());
		}
	}

	protected function ensureDataLoaded()
	{
		if($this->arTaskData === null)
		{
			$this->getData(false);
		}
	}

	protected function getStartDateOrCreatedDate($flag = true)
	{
		$date = null;
		if(empty($this->arTaskData['START_DATE_PLAN']) && !empty($this->arTaskData['CREATED_DATE']) && $flag)
		{
			$date = $this->arTaskData['CREATED_DATE'];
		}
		else
		{
			$date = $this->arTaskData['START_DATE_PLAN'];
		}

		return $date;
	}

	// array access

	public function offsetExists($offset): bool
	{
		try
		{
			$data = $this->getData(false);
		}
		catch (TasksException $e)
		{
			return false;
		}

		return isset($data[$offset]);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		try
		{
			$data = $this->getData(false);
		}
		catch (TasksException $e)
		{
			return null;
		}

		return $data[$offset];
	}

	public function offsetSet($offset , $value): void
	{
		throw new \Bitrix\Main\NotAllowedException('Manual managing of task data is not allowed');
	}

	public function offsetUnset($offset): void
	{
		throw new \Bitrix\Main\NotAllowedException('Manual managing of task data is not allowed');
	}

	############################################################################################
	### Deprecated
	############################################################################################

	/**
	 * @deprecated
	 */
	public function addDependOn($parentId, $linkType = DependenceTable::LINK_TYPE_FINISH_START)
	{
		return $this->addProjectDependence($parentId, $linkType);
	}
	/**
	 * @deprecated
	 */
	public function deleteDependOn($parentId)
	{
		return $this->deleteProjectDependence($parentId);
	}
}