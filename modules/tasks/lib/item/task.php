<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Integration\Search;
use Bitrix\Tasks\Integration\Pull;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Type\DateTime;

use Bitrix\Tasks\Item\Converter\Task\ToTemplate as TaskToTemplate;
use Bitrix\Tasks\Item\Converter\Task\ToTask as TaskToTask;

use \Bitrix\Tasks\Integration\Bizproc;

Loc::loadMessages(__FILE__);

/**
 * Class Task
 * @package Bitrix\Tasks\Item
 *
 * @property string $title
 */
final class Task extends \Bitrix\Tasks\Item
{
	public static function getDataSourceClass()
	{
		return TaskTable::getClass();
	}
	public static function getAccessControllerClass()
	{
		return Access\Task::getClass();
	}
	public static function getUserFieldControllerClass()
	{
		return Util\UserField\Task::getClass();
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Tasks\Control\Task::update()
	 */
	public function save($settings = array())
	{
		$parentTaskId = (isset($this->values['PARENT_ID']))
			? (int)$this->values['PARENT_ID']
			: null;
		$result = parent::save($settings);
		$id = (int) $this->getId();
		if ($id)
		{
			if ($parentTaskId && $parentTaskId !== $id)
			{
				$parentTaskScenarios = ScenarioTable::getList([
					'filter' => [
						'TASK_ID' => $parentTaskId,
					],
					'select' => ['SCENARIO']
				])->fetchCollection();
				$scenarios = [ScenarioTable::SCENARIO_DEFAULT];
				if ($parentTaskScenarios && $parentTaskScenarios->count())
				{
					$scenarios = [];
					foreach ($parentTaskScenarios as $parent)
					{
						$scenarios[] = $parent->getScenario();
					}
				}
				ScenarioTable::insertIgnore($id, $scenarios);
			}
			TaskAccessController::dropItemCache($id);
			TaskMemberService::invalidate();

			(new TimeLineManager($id, $this->userId))->onTaskCreated()->save();
		}
		return $result;
	}

	protected static function generateMap(array $parameters = array())
	{
		$map = parent::generateMap(array(
			'EXCLUDE' => array(
				// deprecated
				'ZOMBIE' => true,

				// will be overwritten below
				'RESPONSIBLE_ID' => true,
				'CREATED_BY' => true,
			)
		));

		$map->placeFields(array(
			// override some tablet fields
			'RESPONSIBLE_ID' => new Task\Field\Legacy\MemberOne(array(
				'NAME' => 'RESPONSIBLE_ID',

				'TYPE' => 'R', // todo: replace with constant

				'SOURCE' => Field\Scalar::SOURCE_TABLET,
				'DB_READABLE' => false, // will be calculated from SE_MEMBER
				'OFFSET_GET_CACHEABLE' => false,
			)),
			'CREATED_BY' => new Task\Field\Legacy\MemberOne(array(
				'NAME' => 'CREATED_BY',
				'TYPE' => 'O', // todo: replace with constant

				'SOURCE' => Field\Scalar::SOURCE_TABLET,
				'DB_READABLE' => false, // will be calculated from SE_MEMBER
				'OFFSET_GET_CACHEABLE' => false,
			)),
			'ACCOMPLICES' => new Task\Field\Legacy\Member(array(
				'NAME' => 'ACCOMPLICES',
				'TYPE' => 'A', // todo: replace with constant

				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
				'OFFSET_GET_CACHEABLE' => false,
			)),
			'AUDITORS' => new Task\Field\Legacy\Member(array(
				'NAME' => 'AUDITORS',
				'TYPE' => 'U', // todo: replace with constant

				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
				'OFFSET_GET_CACHEABLE' => false,
			)),
			'TAGS' => new Task\Field\Legacy\Tag(array(
				'NAME' => 'TAGS',

				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
				'OFFSET_GET_CACHEABLE' => false,
			)),
			'DEPENDS_ON' => new Task\Field\Legacy\DependsOn(array(
				'NAME' => 'DEPENDS_ON',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),

			// todo: ACCOMPLICES, AUDITORS, SE_ACCOMPLICE, SE_AUDITOR, SE_ORIGINATOR, SE_RESPONSIBLE - just aliases for SE_MEMBER with filtering

			// sub-entity
			'SE_CHECKLIST' => new Task\Field\CheckList(array(
				'NAME' => 'SE_CHECKLIST',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),
			'SE_MEMBER' => new Task\Field\Member(array(
				'NAME' => 'SE_MEMBER',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),

			// ingoing gantt dependences
			'SE_PROJECTDEPENDENCE' => new Task\Field\ProjectDependence(array(
				'NAME' => 'SE_PROJECTDEPENDENCE',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),
			'SE_TAG' => new Task\Field\Tag(array(
				'NAME' => 'SE_TAG',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),
			'SE_PARAMETER' => new Task\Field\Parameter(array(
				'NAME' => 'SE_PARAMETER',
				'SOURCE' => Field\Scalar::SOURCE_CUSTOM,
			)),
		));

		return $map;
	}

	public static function getFieldsDescription()
	{
		return static::generateMap();
	}

	public function prepareData($result)
	{
		if(parent::prepareData($result))
		{
			$id = $this->getId();
			$now = $this->getContext()->getNow();

			if(!$this->isFieldModified('CHANGED_DATE'))
			{
				$this['CHANGED_DATE'] = $now;
			}

			// move 0 to null in PARENT_ID to avoid constraint and query problems
			// todo: move PARENT_ID, GROUP_ID and other "foreign keys" to the unique way of keeping absence of relation: null, 0 or ''
			//			if($this->isFieldModified('PARENT_ID'))
			//			{
			//				$parentId = intval($this['PARENT_ID']);
			//				if(!intval($parentId))
			//				{
			//					$this['PARENT_ID'] = false;
			//				}
			//			} // i dont know why

			if(!$id)
			{
				if(!$this->isFieldModified('SITE_ID'))
				{
					$this['SITE_ID'] = $this->getContext()->getSiteId();
				}
				if(!$this->isFieldModified('RESPONSIBLE_ID'))
				{
					$this['RESPONSIBLE_ID'] = $this->getUserId();
				}
				if(!$this->isFieldModified('CREATED_BY'))
				{
					$this['CREATED_BY'] = $this->getUserId();
				}
				if(!$this->isFieldModified('GUID'))
				{
					$this['GUID'] = Util::generateUUID();
				}
				if(!$this->isFieldModified('OUTLOOK_VERSION'))
				{
					$this['OUTLOOK_VERSION'] = 1;
				}

				// force GROUP_ID to 0 if not set (prevent occur as NULL in database)
				$this['GROUP_ID'] = intval($this['GROUP_ID']);

				if(!$this->isFieldModified('CHANGED_BY'))
				{
					$this['CHANGED_BY'] = $this['CREATED_BY'];
				}
				if(!$this->isFieldModified('STATUS_CHANGED_BY'))
				{
					$this['STATUS_CHANGED_BY'] = $this['CHANGED_BY'];
				}

				if(!$this->isFieldModified('CREATED_DATE')) // created date was not set manually
				{
					$this['CREATED_DATE'] = $now;
				}
				if(!$this->isFieldModified('STATUS_CHANGED_DATE'))
				{
					$this['STATUS_CHANGED_DATE'] = $now;
				}
				if(!$this->isFieldModified('ACTIVITY_DATE'))
				{
					$this['ACTIVITY_DATE'] = $now;
				}

				if($this->isFieldModified('DESCRIPTION_IN_BBCODE') && $this['DESCRIPTION_IN_BBCODE'] != 'Y')
				{
					$result->addError('ILLEGAL_DESCRIPTION', 'Tasks with HTML description are not allowed');
				}

				// todo: move scheduler out of here, to the process manager
				$this->processSchedulerBefore();
			}
			else
			{
				// todo
			}
		}

		return $result->isSuccess();
	}

	public function checkData($result)
	{
		parent::checkData($result);
		return $result->isSuccess();
	}

	/**
	 * Runs extra code after actions (save() and delete() performed)
	 *
	 * @param State $state
	 * @return bool
	 */
	protected function doPostActions($state)
	{
		if ($state->isModeCreate())
		{
			// todo: TODO TODO TODO: create processors instead of this operations

			// todo: think about "config object" that will allow to switch off some of these actions,
			// todo: this config object may support hierarchy and partial update
			// todo: example: ['notification' => ['enable' => true, 'add' => ['enable' => false]], 'cache' => ['reset' = ['enable' => true]]]
			// todo: we can use \Bitrix\Tasks\Util\Type\StructureChecker to check such option structure, set default one, etc...

			// todo: refactor this later, get rid of CTasks completely

			// todo: NOTE: for add() this is okay, but for update() some fields may be missing,
			// todo: so its better to use actual data here, not from state (which eventually may be incomplete)

			// todo: BELOW: do other stuff related with SE_ managing and other additional "actions"
			// todo: it must not be hardcoded (as we do traditionally),
			// todo: but should look as some kind of high-level event handling to be able to register, un-register and optionally turn off additional "actions"

			$result = $state->getResult();

			$taskId = $this->getId();
			$userId = $this->getUserId();
			$data = $this->prepareLegacyData();
			$fullTaskData = $this->getData();

			if ($taskId)
			{
				StagesTable::pinInStage($taskId);
			}

			$groupId = $data['GROUP_ID'];
			$parentId = (int)$data['PARENT_ID'];

			$participants = [$data['CREATED_BY'], $data['RESPONSIBLE_ID']];
			if (isset($data['ACCOMPLICES']) && is_array($data['ACCOMPLICES']))
			{
				$participants = array_merge($participants, $data['ACCOMPLICES']);
			}
			if (isset($data['AUDITORS']) && is_array($data['AUDITORS']))
			{
				$participants = array_merge($participants, $data['AUDITORS']);
			}
			$participants = array_unique($participants);

			// add to favorite, if parent is in the favorites too
			if ($parentId && FavoriteTable::check(['TASK_ID' => $parentId, 'USER_ID' => $userId]))
			{
				FavoriteTable::add(['TASK_ID' => $taskId, 'USER_ID' => $userId], ['CHECK_EXISTENCE' => false]);
			}

			if ($groupId)
			{
				ProjectLastActivityTable::update($groupId, ['ACTIVITY_DATE' => $fullTaskData['ACTIVITY_DATE']]);
			}

			// note that setting occur as is deprecated. use access checker switch off instead
			$occurAsUserId = (User::getOccurAsId() ?: $userId);

			\CTaskNotifications::sendAddMessage(
				array_merge($data, ['CHANGED_BY' => $occurAsUserId]),
				[
					'SPAWNED_BY_AGENT' =>
						($data['SPAWNED_BY_AGENT'] ?? null) === 'Y'
						|| ($data['SPAWNED_BY_AGENT'] ?? null) === true,
				]
			);

			\Bitrix\Tasks\Internals\UserOption\Task::onTaskAdd($data);

			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_TASK_ADD,
				$data
			);

			// changes log
			$this->addLogRecord([
				"TASK_ID" => $taskId,
				"USER_ID" => $occurAsUserId,
				"CREATED_DATE" => $fullTaskData['CREATED_DATE'],
				"FIELD" => "NEW",
			], $result);

			Search\Task::index($data); // todo: move this into a special processor
			SearchIndex::setTaskSearchIndex($taskId);

			\CTaskSync::addItem($data); // MS Exchange

			$commentPoster = CommentPoster::getInstance($taskId, $data['CREATED_BY'] ?? 0);
			$commentPoster->postCommentsOnTaskAdd($data);

			$this->sendPullEvents($data, $result);

			$batchState = static::getBatchState();
			$batchState->accumulateArray('USER', $participants);

			if ($groupId)
			{
				$batchState->accumulateArray('GROUP', [$groupId]);
			}

			// todo: this should be moved inside PARENT_ID field controller:
			if ($parentId)
			{
				Dependence::attachNew($taskId, $parentId);
			}

			// todo: move this into a separate processor
			$this->processSchedulerAfter();

			// if batch state is off, this will fire immediately.
			// otherwise, this will fire only when somebody calls ::leaveBatchState() on this class
			$batchState->fireLeaveCallback();

			Bizproc\Listener::onTaskAdd($taskId, $data);
		}
		elseif ($state->isModeUpdate())
		{
			// todo: DO NOT remove template in case of REPLICATE falls to N
			$taskId = $this->getId();
			if ($taskId)
			{
				StagesTable::pinInStage($taskId);
			}
		}
	}

	/**
	 * @param State $state
	 * @return boolean
	 */
	protected function executeHooksBefore($state)
	{
		$this->fireLegacyEvent($state);

		return $state->getResult()->isSuccess();
	}

	/**
	 * @param State $state
	 * @return boolean
	 */
	protected function executeHooksAfter($state)
	{
		$this->fireLegacyEvent($state, false);

		return $state->getResult()->isSuccess();
	}

	/**
	 * Compatibility
	 *
	 * @param State $state
	 * @param bool $isBefore
	 * @return bool
	 */
	private function fireLegacyEvent($state, $isBefore = true)
	{
		$result = $state->getResult();

		$before = $isBefore ? 'Before' : '';
		$canAlterData = false;

		$arFields = $arFieldsSource = array();

		if($state->isModeCreate())
		{
			$name = 'On'.$before.'TaskAdd';
			$unknownErrorMessage = \GetMessage("TASKS_UNKNOWN_ADD_ERROR");

			$arFields = $arFieldsSource = $this->prepareLegacyData();

			if($isBefore)
			{
				$arguments = array(&$arFields);
				$canAlterData = true;
			}
			else
			{
				$arguments = array($this->getId(), &$arFields);
			}
		}
		elseif($state->isModeUpdate())
		{
			$name = 'On'.$before.'TaskUpdate';
			$unknownErrorMessage = \GetMessage("TASKS_UNKNOWN_UPDATE_ERROR");

			$arFields = $arFieldsSource = $this->prepareLegacyData(false, true);
			$arTaskCopy = $this->prepareLegacyData(true);

			$arguments = array($this->getId(), &$arFields, &$arTaskCopy);
			$canAlterData = true;
		}
		elseif($state->isModeDelete())
		{
			$name = 'On'.$before.'TaskDelete';
			$unknownErrorMessage = 'Unknown delete error';

			$arTaskCopy = $this->prepareLegacyData(true);

			$arguments = array($this->getId(), &$arTaskCopy);
		}
		else
		{
			return true;
		}

		global $APPLICATION;

		$executedOnce = false;
		foreach(GetModuleEvents('tasks', $name, true) as $event)
		{
			$executedOnce = true;
			$execResult = 0;

			if(array_key_exists('CALLBACK', $event))
			{
				$handlerName = 'Closure';
			}
			else
			{
				$handlerName = $event['TO_CLASS'].'::'.$event['TO_METHOD'].'();';
			}

			try
			{
				$execResult = ExecuteModuleEventEx($event, $arguments);
			}
			catch(\Exception $e)
			{
				$result->addException($e, 'Exception in event handler: '.$handlerName);
			}

			if($execResult === false)
			{
				$e = $APPLICATION->getException();

				$hasExplanation = false;
				if($e instanceof \CAdminException)
				{
					if (is_array($e->messages))
					{
						foreach($e->messages as $msg)
						{
							$hasExplanation = true;
							$result->addError('EVENT_HANDLER_ERROR', $msg);
						}
					}
				}
				else
				{
					$hasExplanation = true;
					$result->addError('EVENT_HANDLER_ERROR.'.$e->getId(), $e->getString());
				}

				if(!$hasExplanation)
				{
					$result->addError('EVENT_HANDLER_ERROR', $unknownErrorMessage);
				}

				return false;
			}
		}

		if($canAlterData && $executedOnce)
		{
			// find difference between $arFields and $arFieldsSource, and update $this

			foreach($arFields as $key => $newValue)
			{
				// key was added or changed
				if(!array_key_exists($key, $arFieldsSource) || $arFields[$key] != $arFieldsSource[$key])
				{
					$this[$key] = $newValue;
				}
			}

			foreach($arFieldsSource as $key => $oldValue)
			{
				// key was removed
				if(!array_key_exists($key, $arFields))
				{
					$this[$key] = null;
				}
			}
		}

		return true;
	}

	public static function processEnterBatchMode(State\Trigger $state)
	{
		\CTaskNotifications::disableAutoDeliver(); // start buffering notifications
	}

	public static function processLeaveBatchMode(State\Trigger $state)
	{
		global $CACHE_MANAGER;

		$users = $state->getArray('USER');
		$groups = $state->getArray('GROUP');

		// todo: think about "config object" that will allow to switch off some of these actions

		foreach($groups as $group)
		{
			$CACHE_MANAGER->ClearByTag("tasks_group_".$group);
			Group::updateLastActivity($group);
		}
		foreach($users as $userId)
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_".$userId);
		}

		\CTaskNotifications::enableAutoDeliver(); // flush buffer and stop buffering

		return new Result(); // formally
	}

	public function getShortPreview()
	{
		return $this['TITLE'].' ['.$this->getId().']';
	}

	public function getDuration($start = null, $end = null, array $parameters = array())
	{
		if($start === null)
		{
			$start = $this['START_DATE_PLAN'];
		}
		if($end === null)
		{
			$end = $this['END_DATE_PLAN'];
		}

		$start = DateTime::createFrom($start, 0);
		$end = DateTime::createFrom($end, 0);

		if($end === null)
		{
			return INF;
		}
		if($start == null)
		{
			return -INF;
		}

		if(array_key_exists('MATCH_WORK_TIME', $parameters))
		{
			$matchWorkTime = $parameters['MATCH_WORK_TIME'];
		}
		else
		{
			$matchWorkTime = $this['MATCH_WORK_TIME'];
		}
		$matchWorkTime = $matchWorkTime == 'Y' || $matchWorkTime === true || $matchWorkTime === 1  || $matchWorkTime === '1';

		if($matchWorkTime)
		{
			$calendar = new Util\Calendar();
			return $calendar->calculateDuration($start, $end);
		}
		else
		{
			return $end->getTimestamp() - $start->getTimestamp();
		}
	}

	/**
	 * Set task change time to the specified value
	 *
	 * @param null $time
	 * @return $this
	 */
	public function touch($time = null)
	{
		if(!$this->isImmutable())
		{
			if($time === null)
			{
				$time = $this->getContext()->getNow();
			}

			$this['CHANGED_DATE'] = UI::formatDateTime($time);
		}

		return $this;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Tasks\Control\Task::update()
	 * @see \CTaskItem::complete()
	 *
	 * Set task status to 'completed' or 'awaiting approval'
	 *
	 * @param array $params
	 *
	 * @return $this
	 * @throws \Bitrix\Main\SystemException
	 */
	public function complete(array $params = array())
	{
		if(!$this->isImmutable())
		{
			$status = 5;
			if($this->taskControl == 'Y' && $this->userId == $this->responsibleId && $this->userId != $this->createdBy)
			{
				$status = 4;
			}

			$this->status = $status;
			$this->save($params);
		}

		return $this;
	}

	/**
	 * Creates new virtual (not presented in database) instance of \Bitrix\Tasks\Item\Task\Template based on
	 * data from $this
	 *
	 * @return Converter\Result
	 */
	public function transformToTemplate()
	{
		// todo: better to use the same converter over and over again, so use object pool here when ready
		$converter = new TaskToTemplate();

		return $this->transform($converter);
	}

	/**
	 * Creates new virtual (not presented in database) instance of \Bitrix\Tasks\Item\Task based on
	 * data from $this
	 *
	 * @return Converter\Result
	 */
	public function transformToTask()
	{
		// todo: better to use the same converter over and over again, so use object pool here when ready
		$converter = new TaskToTask();

		return $this->transform($converter);
	}

	private function sendPullEvents($data, $result)
	{
		$id = $this->getId();
		$recipients = array_merge(array($data["CREATED_BY"], $data["RESPONSIBLE_ID"]), $data["ACCOMPLICES"], $data["AUDITORS"]);

		try
		{
			$groupId = intval($data['GROUP_ID']);

			$lastResult = ResultManager::getLastResult((int) $id);
			$arPullData = array(
				'TASK_ID' => $id,
				'AFTER' => array(
					'GROUP_ID' => $groupId
				),
				'params' => [
					'addCommentExists' => false
				],
				'TS' => time(),
				'event_GUID' => isset($data['META::EVENT_GUID']) ? $data['META::EVENT_GUID'] : sha1(uniqid('AUTOGUID', true)),
				'taskRequireResult' => \Bitrix\Tasks\Internals\Task\Result\ResultManager::requireResult((int)$id) ? "Y" : "N",
				'taskHasResult' => $lastResult ? "Y" : "N",
				'taskHasOpenResult' => ($lastResult && (int) $lastResult['STATUS'] === ResultTable::STATUS_OPENED) ? "Y" : "N",
			);

			Pull\PushService::addEvent($recipients, [
				'module_id'  => 'tasks',
				'command'    => Pull\PushCommand::TASK_ADDED,
				'params'     => $arPullData
			]);
		}
		catch (\Exception $e)
		{
			Util::log($e);
			$result->getErrors()->addWarning('POST_ACTION_FAILURE.PULL', Loc::getMessage('TASKS_ITEM_TASK_PULL_NOT_SENT'), array('EXCEPTION' => $e));
		}
	}

	private function addLogRecord($logData, $result)
	{
		$addResult = LogTable::add($logData);
		$result->adoptErrors($addResult, array(
			'TYPE' => Util\Error::TYPE_WARNING,
			'CODE' => 'POST_ACTION.LOG',
			'MESSAGE' => Loc::getMessage('TASKS_ITEM_TASK_LOG_NOT_CREATED').': #MESSAGE#',
		));
	}

	/**
	 * Compatibility
	 */
	private function prepareLegacyData($pristine = false, $onlyModified = false)
	{
		$allowed = array_merge(array(
			'ID',
			'PRIORITY',
			'TITLE',
			'DESCRIPTION',
			'DESCRIPTION',
			'DEADLINE',
			'START_DATE_PLAN',
			'DURATION_TYPE',
			'END_DATE_PLAN',
			'ALLOW_CHANGE_DEADLINE',
			'MATCH_WORK_TIME',
			'TASK_CONTROL',
			'ALLOW_TIME_TRACKING',
			'TIME_ESTIMATE',
			'REPLICATE',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'AUDITORS',
			'ACCOMPLICES',
			'TAGS',
			'DEPENDS_ON',
			'PARENT_ID',
			'GROUP_ID',
			'CHANGED_BY',
			'CHANGED_DATE',
			'OUTLOOK_VERSION',
			'DURATION_PLAN',
		), $this->getMap()->getUserFieldNames());

		if($onlyModified)
		{
			$modified = $this->getModifiedFields();
			$modified[] = 'ID';

			$allowed = array_intersect($allowed, $modified);
		}

		if($pristine)
		{
			$this->setDataContext('pristine');
		}

		$data = $this->export($allowed);

		if($pristine)
		{
			$this->setDefaultDataContext();
		}

		$data['SE_TAG'] = '';

		/** @var \Bitrix\Tasks\Item\Task\Collection\Tag $tags */
		$tags = $this['SE_TAG'];
		if($tags)
		{
			$data['SE_TAG'] = $tags->joinNames();
		}

		return $data;
	}

	/**
	 * this will work only for add()
	 * @see \CTasks::Add
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function processSchedulerBefore()
	{
		$shiftResult = null;
		if((string) $this['START_DATE_PLAN'] != '' || (string) $this['END_DATE_PLAN'] != '')
		{
			$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->getUserId());
			$shiftResult = $scheduler->processEntity(0, $this->getData('~'), array(
				'MODE' => 'BEFORE_ATTACH',
			));
			if($shiftResult->isSuccess())
			{
				$shiftData = $shiftResult->getImpactById(0);
				if($shiftData)
				{
					// will be saved...
					$this['START_DATE_PLAN'] = $shiftData['START_DATE_PLAN'];
					$this['END_DATE_PLAN'] = $shiftData['END_DATE_PLAN'];
					$this['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];
				}

				$this->getTransitionState()->setValue('PROCESSOR.SCHEDULER.RESULT', $shiftResult);
			}
		}
	}

	/**
	 * this will work only for add()
	 * @see \CTasks::Add
	 */
	private function processSchedulerAfter()
	{
		$shiftResult = $this->getTransitionState()->get('PROCESSOR.SCHEDULER.RESULT');
		if($shiftResult instanceof \Bitrix\Tasks\Processor\Task\Result)
		{
			$shiftResult->save(array('!ID' => 0));
		}
	}
}