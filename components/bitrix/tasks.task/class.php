<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListConverterHelper;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListConverterHelper;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\Forum\Task\Topic;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Type\Structure;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Access\Model\TaskModel;

Loc::loadMessages(__FILE__);

require_once(dirname(__FILE__).'/class/formstate.php');

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskComponent extends TasksBaseComponent
{
	const ERROR_TYPE_TASK_SAVE_ERROR = 'TASK_SAVE_ERROR';

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
			if ($request['ACTION'][0]['ARGUMENTS']['taskId'])
			{
				$taskId = (int) $request['ACTION'][0]['ARGUMENTS']['taskId'];
			}
			elseif ($request['ACTION'][0]['ARGUMENTS']['params']['TASK_ID'])
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
			&& $request['ACTION'][0]['OPERATION'] === 'task.add'
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
			&& $request['ACTION'][0]['OPERATION'] === 'TasksTaskComponent.saveCheckList'
		)
		{
			$action = Tasks\Access\ActionDictionary::ACTION_CHECKLIST_SAVE;
			$accessCheckParams = isset($request['ACTION'][0]['ARGUMENTS']['items']) ? $request['ACTION'][0]['ARGUMENTS']['items'] : [];
			$error->setMessage(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
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

		if(Type::isIterable($request['ADDITIONAL']))
		{
			$this->setDataSource(
				$request['ADDITIONAL']['DATA_SOURCE']['TYPE'],
				$request['ADDITIONAL']['DATA_SOURCE']['ID']
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
				$this->setEventOption('STAY_AT_PAGE', 'Y');
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
		$this->handleCalendarEvent();
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

	private function handleCalendarEvent(): void
	{
		$operationResult = $this->getTaskActionResult();
		$isTaskAdding = ($operationResult['OPERATION'] === 'task.add');

		if ($isTaskAdding && ($calendarEventId = (int)$this->request->get('CALENDAR_EVENT_ID')))
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

			\Bitrix\Tasks\AnalyticLogger::logToFile(
				'finish',
				'firstGridTaskCreation',
				'',
				'tourGuide'
			);
		}
	}

	private static function getOperationTaskId(array $operation)
	{
		return intval($operation['RESULT']['DATA']['ID']); // task.add and task.update always return TASK_ID on success
	}

	private function makeRedirectUrl(array $operation)
	{
		$actionAdd = $operation['OPERATION'] == 'task.add';
		$resultTaskId = static::getOperationTaskId($operation);

		$backUrl = $this->getBackUrl();
		$url = $backUrl != '' ? Util::secureBackUrl($backUrl) : $GLOBALS["APPLICATION"]->GetCurPageParam('');

		$action = 'view'; // having default backurl after success edit we go to view ...

		// .. but there are some exceptions
		$taskId = 0;
		if($actionAdd)
		{
			$taskId = $resultTaskId;
			if($this->request['STAY_AT_PAGE'])
			{
				$taskId = 0;
				$action = 'edit';
			}
		}

		$url = UI\Task::makeActionUrl($url, $taskId, $action);
		$url = UI\Task::cleanFireEventUrl($url);
		$url = UI\Task::makeFireEventUrl($url, $this->getEventTaskId(), $this->getEventType(), array(
			'STAY_AT_PAGE' => $this->getEventOption('STAY_AT_PAGE')
		));

		if($actionAdd && $this->request['STAY_AT_PAGE']) // reopen form with the same parameters as the previous one
		{
			$initial = $this->hitState->exportFlat('INITIAL_TASK_DATA', '.');
			// todo: a little spike for tags, refactor that later
			if(array_key_exists('TAGS.0', $initial))
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
		if($additional && $additional['SAVE_AS_TEMPLATE'] == 'Y') // user specified he wanted to create template by this task
		{
			if (!Tasks\Access\TemplateAccessController::can($this->userId, Tasks\Access\ActionDictionary::ACTION_TEMPLATE_CREATE))
			{
				$this->errors->addWarning(
					'SAVE_AS_TEMPLATE_ERROR',
					Loc::getMessage('TASKS_TT_SAVE_AS_TEMPLATE_ERROR_MESSAGE_PREFIX') . ': ' . Loc::getMessage('TASKS_TEMPLATE_CREATE_FORBIDDEN')
				);
				return;
			}
			$task = new \Bitrix\Tasks\Item\Task($taskId, $this->userId); // todo: use Task::getInstance($taskId, $this->userId) here, when ready
			if($task['REPLICATE'] != 'Y')
			{
				// create template here
				$conversionResult = $task->transformToTemplate();
				if($conversionResult->isSuccess())
				{
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


			}
			// or else it was created above, inside \Bitrix\Tasks\Manager\Task\Template::manageTaskReplication()
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
						$checkListItems[$id]['IS_COMPLETE'] = ($item['IS_COMPLETE'] === 'true');
						$checkListItems[$id]['IS_IMPORTANT'] = ($item['IS_IMPORTANT'] === 'true');
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

		if (!intval($source['ID']))
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

			'ALLOW_CHANGE_DEADLINE' =>  $stateFlags['ALLOW_CHANGE_DEADLINE'] ? 'Y' : 'N',
			'ALLOW_TIME_TRACKING' => 	$stateFlags['ALLOW_TIME_TRACKING'] ? 'Y' : 'N',
			'TASK_CONTROL' => 			$stateFlags['TASK_CONTROL'] ? 'Y' : 'N',
			'MATCH_WORK_TIME' => 		$stateFlags['MATCH_WORK_TIME'] ? 'Y' : 'N',

			'DESCRIPTION_IN_BBCODE' => 'Y', // new tasks should be always in bbcode
			'DURATION_TYPE' => 		CTasks::TIME_UNIT_TYPE_DAY,
			'DURATION_TYPE_ALL' =>  CTasks::TIME_UNIT_TYPE_DAY,

			'SE_PARAMETER' => array(
				array('NAME' => 'PROJECT_PLAN_FROM_SUBTASKS', 'VALUE' => 'Y')
			),

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
		$title = $this->hitState->get('INITIAL_TASK_DATA.TITLE');
		if ($title !== '')
		{
			$data['TITLE'] = $title;
		}

		// description
		$description = $this->hitState->get('INITIAL_TASK_DATA.DESCRIPTION');
		if ($description !== '' && $this->request->get('CALENDAR_EVENT_ID'))
		{
			$data['DESCRIPTION'] = $description;
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

		return ['DATA' => $data];
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

		$this->arResult['COMPONENT_DATA']['FIRST_GRID_TASK_CREATION_TOUR_GUIDE'] =
			$this->request->get('FIRST_GRID_TASK_CREATION_TOUR_GUIDE')
		;

		$formSubmitted = $this->formData !== false;

		if($this->task != null) // editing an existing task, get THIS task data
		{
			$data = Task::get($this->userId, $this->task->getId(), array(
				'ENTITY_SELECT' => $this->arParams['SUB_ENTITY_SELECT'],
				'ESCAPE_DATA' => static::getEscapedData(), // do not delete
				'ERRORS' => $this->errors
			));
			$this->arResult['DATA']['CHECKLIST_CONVERTED'] = TaskCheckListConverterHelper::checkEntityConverted($this->task->getId());

			if($this->errors->checkHasFatals())
			{
				return;
			}

			if($formSubmitted)
			{
				// applying form data on top, what changed
				$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);
			}

			$group = Bitrix\Socialnetwork\Item\Workgroup::getById($data['DATA']['GROUP_ID']);
			$this->arParams['IS_SCRUM_TASK'] = ($group && $group->isScrumProject());
		}
		else // get from other sources: default task data, or other task data, or template data
		{
			$data = $this->getDataDefaults();
			$this->arResult['DATA']['CHECKLIST_CONVERTED'] = true;

			if($formSubmitted)
			{
				// applying form data on top, what changed
				$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);
			}
			else
			{
				$copyErrors = new Collection();
				$parameters = array(
					'ENTITY_SELECT' => array_intersect($this->arParams['SUB_ENTITY_SELECT'], array('CHECKLIST', 'REMINDER', 'TAG', 'PROJECTDEPENDENCE', 'RELATEDTASK')),
					'ESCAPE_DATA' => false,
					'ERRORS' => $copyErrors
				);

				$error = false;
				$sourceData = [];

				try
				{
					if ($templateId = (int)$this->request['TEMPLATE']) // copy from template?
					{
						$request = Application::getInstance()->getContext()->getRequest();

						$sourceData = $this->cloneDataFromTemplate($templateId);
						$fields = ['UF_CRM_TASK', 'TAGS'];

						foreach ($fields as $fieldName)
						{
							$fieldValue = $request->getQuery($fieldName);
							if ($fieldValue)
							{
								if ($fieldName == 'TAGS')
								{
									$tags = explode(',', $fieldValue);
									foreach ($tags as $tag)
									{
										$sourceData['DATA']['TAGS'][] = $tag;
										$sourceData['DATA']['SE_TAG'][] = ['NAME' => $tag];
									}
								}
								else if ($fieldName == 'UF_CRM_TASK')
								{
									$sourceData['DATA'][$fieldName][] = $fieldValue;
								}
							}
						}

						$driver = \Bitrix\Disk\Driver::getInstance();
						$userFieldManager = $driver->getUserFieldManager();
						$attachedObjects = $userFieldManager->getAttachedObjectByEntity(
							'TASKS_TASK_TEMPLATE',
							$templateId,
							'UF_TASK_WEBDAV_FILES'
						);

						foreach ($attachedObjects as $attachedObject)
						{
							$sourceData['DATA']['DISK_ATTACHED_OBJECT_ALLOW_EDIT'] = $attachedObject->getAllowEdit();
							break;
						}

						$this->setDataSource(static::DATA_SOURCE_TEMPLATE, $this->request['TEMPLATE']);
						$this->arResult['DATA']['CHECKLIST_CONVERTED'] = TemplateCheckListConverterHelper::checkEntityConverted($templateId);
					}
					elseif ((int)$this->request['COPY'] || (int)$this->request['_COPY']) // copy from another task?
					{
						$taskIdToCopy = ((int)$this->request['COPY']?: (int)$this->request['_COPY']);
						$sourceData = $this->cloneDataFromTask($taskIdToCopy);

						$this->setDataSource(static::DATA_SOURCE_TASK, $taskIdToCopy);
						$this->arResult['DATA']['CHECKLIST_CONVERTED'] = TaskCheckListConverterHelper::checkEntityConverted($taskIdToCopy);
					}
					else // get some from request
					{
						$sourceData = $this->getDataRequest();
					}
				}
				catch(TasksException $e)
				{
					if($e->checkOfType(TasksException::TE_ACCESS_DENIED) || $e->checkOfType(TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
					{
						$error = 'access';
					}
					else
					{
						$error = 'other';
					}
				}
				catch(\Bitrix\Tasks\AccessDeniedException $e)
				{
					$error = 'access';
				}
				if($error === false) // no exceptions? may be error collection has any?
				{
					if(!$copyErrors->isEmpty())
					{
						$error = 'other';
					}
				}

				if($error !== false)
				{
					$this->errors->add('COPY_ERROR', Loc::getMessage($error == 'access' ? 'TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE_COPY' : 'TASKS_TT_COPY_READ_ERROR'), Collection::TYPE_WARNING);
				}

				$data['DATA'] = Task::mergeData($sourceData['DATA'], $data['DATA']);

				if ($data['DATA']['SE_ORIGINATOR']['ID'] !== $this->userId &&
					(count($data['DATA']['SE_RESPONSIBLE']) > 1 || (count($data['DATA']['SE_RESPONSIBLE']) == 1 && $data['DATA']['SE_RESPONSIBLE'][0]['ID'] !== $this->userId)))
				{
					$data['DATA']['SE_ORIGINATOR']['ID'] = $this->userId;
					$this->errors->addWarning('', Loc::getMessage('TASKS_TT_AUTO_CHANGE_ORIGINATOR'));
				}
			}
		}

		// kanban stages
		if ($data['DATA']['GROUP_ID'] > 0)
		{
			if ($this->arParams['IS_SCRUM_TASK'])
			{
				$kanbanService = new KanbanService();

				$this->arResult['DATA']['STAGES'] = $kanbanService->getStagesToTask($data['DATA']['ID']);
				$data['DATA']['STAGE_ID'] = $kanbanService->getTaskStageId($data['DATA']['ID']);
			}
			else
			{
				$this->arResult['DATA']['STAGES'] = StagesTable::getStages(
					$data['DATA']['GROUP_ID'],
					true
				);
			}

			$data['CAN']['ACTION']['SORT'] = Loader::includeModule('socialnetwork') &&
											SocialNetwork\Group::can(
												$data['DATA']['GROUP_ID'],
												SocialNetwork\Group::ACTION_SORT_TASKS
											);
		}
		else
		{
			$this->arResult['DATA']['STAGES'] = array();
			$data['CAN']['ACTION']['SORT'] = false;
		}

		$this->arResult['DATA']['TASK'] = $data['DATA'];
		$this->arResult['CAN']['TASK'] = $data['CAN'];

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
			foreach (array_keys($checkListItems) as $id)
			{
				$checkListItems[$id]['COPIED_ID'] = $id;
				unset($checkListItems[$id]['ID']);
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
		$data = array();

		$task = new Tasks\Item\Task($itemId, $this->userId);
		$result = $task->transform(new Tasks\Item\Converter\Task\ToTask());
		if($result->isSuccess())
		{
			$data = Task::convertFromItem($result->getInstance());

			// exception for responsibles, it may be multiple in the form
			$data['SE_RESPONSIBLE'] = array(array('ID' => $task['RESPONSIBLE_ID']));

			$checkListItems = TaskCheckListFacade::getItemsForEntity($itemId, $this->userId);
			foreach (array_keys($checkListItems) as $id)
			{
				$checkListItems[$id]['COPIED_ID'] = $id;
				unset($checkListItems[$id]['ID']);
			}
			$data['SE_CHECKLIST'] = $checkListItems;
		}

		return array('DATA' => $data);
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

		$this->collectMembersFromArray(Task\Originator::extractPrimaryIndexes($data[Task\Originator::getCode(true)]));
		$this->collectMembersFromArray(Task\Responsible::extractPrimaryIndexes($data[Task\Responsible::getCode(true)]));
		$this->collectMembersFromArray(Task\Accomplice::extractPrimaryIndexes($data[Task\Accomplice::getCode(true)]));
		$this->collectMembersFromArray(Task\Auditor::extractPrimaryIndexes($data[Task\Auditor::getCode(true)]));
		$this->collectMembersFromArray(array(
			$this->arResult['DATA']['TASK']['CHANGED_BY'],
			$this->userId
		));
	}

	protected function collectRelatedTasks()
	{
		if($this->arResult['DATA']['TASK']['PARENT_ID'])
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK']['PARENT_ID'];
		}
		elseif($this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)])
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)]['ID'];
		}

		if(Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE']))
		{
			$projdep = $this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE'];
			foreach($projdep as $dep)
			{
				$this->tasks2Get[] = $dep['DEPENDS_ON_ID'];
			}
		}

		if(Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK']))
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
		if($this->arResult['DATA']['TASK']['GROUP_ID'])
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK']['GROUP_ID'];
		}
		elseif($this->arResult['DATA']['TASK'][Task\Project::getCode(true)])
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID'];
		}
//		$this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID'] = $this->arResult['DATA']['TASK']['GROUP_ID']; // ?
	}

	protected function collectLogItems()
	{
		if (!Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG']))
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
		$this->arResult['DATA']['GROUP'] = Group::getData($this->groups2Get, ['IMAGE_ID']);
		$this->arResult['DATA']['USER'] = User::getData($this->users2Get);

		$this->getCurrentUserData();
	}

	protected function getCurrentUserData()
	{
		$currentUser = array('DATA' => $this->arResult['DATA']['USER'][$this->userId]);

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

	protected function formatData()
	{
		$data =& $this->arResult['DATA']['TASK'];

		if(Type::isIterable($data))
		{
			Task::extendData($data, $this->arResult['DATA']);

			// left for compatibility
			$data[Task::SE_PREFIX.'PARENT'] = $data[Task\ParentTask::getCode(true)];
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
				ViewedTable::set(
					$this->task->getId(),
					$this->userId,
					null,
					['UPDATE_TOPIC_LAST_VISIT' => false]
				);
				if ($this->arParams['PLATFORM'] === 'web')
				{
					$this->arResult['DATA']['EFFECTIVE'] = $this->getEffective();
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
		if($this->getEventTaskId() && ($this->formData === false || $this->success))
		{
			/*
			form had not been submitted at the current hit, or submitted successfully
			*/

			$eventTaskData = false;
			if($this->task != null && $this->task->getId() == $this->getEventTaskId())
			{
				$eventTaskData = static::dropSubEntitiesData($this->arResult['DATA']['TASK']);
			}
			else // have to get data manually
			{
				try
				{
					$eventTask = Task::get($this->userId, $this->getEventTaskId());
					if($eventTask['ERRORS']->checkNoFatals())
					{
						$eventTaskData = $eventTask['DATA'];
					}
				}
				catch(Tasks\Exception $e) // smth went wrong - no access or smth else. just skip, what else to do?
				{
				}
			}

			// happy end
			if(Type::isIterable($eventTaskData) && !empty($eventTaskData))
			{
				$eventTaskData['CHILDREN_COUNT'] = 0;
				$childrenCount = CTasks::GetChildrenCount(array(), $eventTaskData['ID'])->fetch();
				if ($childrenCount)
				{
					$eventTaskData['CHILDREN_COUNT'] = $childrenCount['CNT'];
				}

				$this->arResult['DATA']['EVENT_TASK'] = $eventTaskData;
				$this->arResult['COMPONENT_DATA']['EVENT_TYPE'] = $this->getEventType();

				$sap = $this->getEventOption('STAY_AT_PAGE');
				//TODO !!!
				if ($this->getEventType() == 'UPDATE')
				{
					$sap = true;
				}

				$this->arResult['COMPONENT_DATA']['EVENT_OPTIONS'] = array(
					'STAY_AT_PAGE' => $sap
				);
			}
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

				list($list, $res) = CTaskItem::fetchList(
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
		return $this->arResult['COMPONENT_DATA']['DATA_SOURCE'];
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
		if(Type::isIterable($this->request['EVENT_OPTIONS']) && isset($this->request['EVENT_OPTIONS'][$name]))
		{
			$this->eventOptions[$name] = !!$this->request['EVENT_OPTIONS'][$name];
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

	// for dispatcher below

	public static function getAllowedMethods()
	{
		return array(
			'setState',
			'getFiles',
			'getFileCount',
			'saveCheckList',
		);
	}

	public function getHitState()
	{
		return $this->hitState;
	}

	public static function setState(array $state = array())
	{
		TasksTaskFormState::set($state);
	}

	public static function getState()
	{
		return TasksTaskFormState::get();
	}

	public static function getFiles($params)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:disk.uf.comments.attached.objects",
			".default",
			array(
				"MAIN_ENTITY" => array(
					"ID" => $params["TASK_ID"]
				),
				"COMMENTS_MODE" => "forum",
				"ENABLE_AUTO_BINDING_VIEWER" => false, // Viewer cannot work in the iframe (see logic.js)
				"DISABLE_LOCAL_EDIT" => $params["PUBLIC_MODE"],
				"COMMENTS_DATA" => array(
					"TOPIC_ID" => $params["FORUM_TOPIC_ID"],
					"FORUM_ID" => $params["FORUM_ID"],
					"XML_ID" => "TASK_".$params["TASK_ID"]
				),
				"PUBLIC_MODE" => $params["PUBLIC_MODE"]
			),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);
		$html = ob_get_contents();
		ob_end_clean();

		return array("html" => $html);
	}

	public static function getFileCount($params)
	{
		$fileCount = 0;
		if ($params["FORUM_ID"] > 0 && $params["FORUM_TOPIC_ID"] > 0)
		{
			$fileCount = Topic::getFileCount($params["FORUM_TOPIC_ID"], $params["FORUM_ID"]);
		}
		return array("fileCount" => $fileCount);
	}

	/**
	 * @param $items
	 * @param $taskId
	 * @param $userId
	 * @param $params
	 * @return array|Util\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function saveCheckList($items, $taskId, $userId, $params)
	{
		$result = new Util\Result();

		if (isset($params['openTime']) && $params['openTime'])
		{
			$openTime = $params['openTime'];
			$lastUpdateTime = Tasks\Internals\Task\LogTable::getList([
				'select' => ['CREATED_DATE'],
				'filter' => [
					'TASK_ID' => $taskId,
					'!USER_ID' => $userId,
					'%FIELD' => 'CHECKLIST',
				],
				'order' => ['CREATED_DATE' => 'DESC'],
				'limit' => 1,
			])->fetch();

			if ($lastUpdateTime)
			{
				$lastUpdateTime = $lastUpdateTime['CREATED_DATE']->getTimestamp();
				if ($lastUpdateTime > $openTime)
				{
					$result->setData(['PREVENT_CHECKLIST_SAVE' => 'It looks like someone has already changed checklist.']);
					return $result;
				}
			}
		}

		if (!is_array($items))
		{
			$items = [];
		}

		foreach ($items as $id => $item)
		{
			$item['ID'] = ((int)$item['ID'] === 0? null : (int)$item['ID']);
			$item['IS_COMPLETE'] = (int)$item['IS_COMPLETE'] > 0;
			$item['IS_IMPORTANT'] = (int)$item['IS_IMPORTANT'] > 0;

			if (is_array($item['MEMBERS']))
			{
				$members = [];

				foreach ($item['MEMBERS'] as $member)
				{
					$members[key($member)] = current($member);
				}

				$item['MEMBERS'] = $members;
			}

			$items[$item['NODE_ID']] = $item;
			unset($items[$id]);
		}

		$result = TaskCheckListFacade::merge($taskId, $userId, $items, $params);
		$result->setData(array_merge(($result->getData() ?? []), ['OPEN_TIME' => (new DateTime())->getTimestamp()]));

		return $result;
	}
}

if(CModule::IncludeModule('tasks'))
{
	final class TasksTaskHitStateStructure extends Structure
	{
		public function __construct($request)
		{
			$hitState = array();
			if(array_key_exists('HIT_STATE', $request))
			{
				$hitState = $request['HIT_STATE'];
			}

			// todo: also add BACKURL, CANCELURL, DATA_SOURCE here for compatibility, to keep this data inside hit state

			parent::__construct($hitState);
		}

		public function getRules()
		{
			return [
				'INITIAL_TASK_DATA' => [
					'VALUE' => [
						'PARENT_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
						'RESPONSIBLE_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
						'AUDITORS' => [
							'VALUE' => StructureChecker::TYPE_ARRAY_OF_STRING,
							'CAST' => function($value) {
								return (
									is_array($value)
										? $value
										: array_map('trim', explode(',', $value))
								);
							},
						],
						'GROUP_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
						'TITLE' => ['VALUE' => StructureChecker::TYPE_STRING],
						'DESCRIPTION' => ['VALUE' => StructureChecker::TYPE_STRING],
						Integration\CRM\UserField::getMainSysUFCode() => ['VALUE' => StructureChecker::TYPE_STRING],
						Integration\Mail\UserField::getMainSysUFCode() => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
						'TAGS' => [
							'VALUE' => StructureChecker::TYPE_ARRAY_OF_STRING,
							'CAST' => function($value) {
								return (
									is_array($value)
										? $value
										: array_map('trim', explode(',', $value))
								);
							},
						],
						'DEADLINE' => ['VALUE' => StructureChecker::TYPE_STRING],
						'START_DATE_PLAN' => ['VALUE' => StructureChecker::TYPE_STRING],
						'END_DATE_PLAN' => ['VALUE' => StructureChecker::TYPE_STRING],
					],
					'DEFAULT' => [],
				],
				'BACKURL' => ['VALUE' => StructureChecker::TYPE_STRING],
				'CANCELURL' => ['VALUE' => StructureChecker::TYPE_STRING],
				'DATA_SOURCE' => [
					'VALUE' => [
						'TYPE' => ['VALUE' => StructureChecker::TYPE_STRING],
						'ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					],
					'DEFAULT' => [],
				],
			];
		}
	}
}
