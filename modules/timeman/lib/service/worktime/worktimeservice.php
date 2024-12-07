<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Contract\WorktimeRecordIdStorable;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\BaseServiceResult;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeAction;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeActionList;
use Bitrix\Timeman\Service\Worktime\Notification\WorktimeNotificationService;
use Bitrix\Timeman\Service\Worktime\Record;
use Bitrix\Timeman\Service\Worktime\Record\WorktimeManagerFactory;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;

Loc::loadMessages(__FILE__);

class WorktimeService extends BaseService
{
	/** @var WorktimeActionList */
	private $actionList;
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var WorktimeNotificationService */
	private $notificationService;
	/** @var WorktimeManagerFactory */
	private $worktimeManagerFactory;
	/** @var WorktimeRecordForm */
	private $recordForm;
	private $worktimeAgentManager;
	private $liveFeedManager;
	/** @var Record\WorktimeManager|null */
	private $worktimeManager;

	public function __construct(
		WorktimeManagerFactory $recordFactory,
		WorktimeAgentManager $worktimeAgentManager,
		WorktimeActionList $actionList,
		WorktimeRepository $worktimeRepository,
		WorktimeNotificationService $notificationService,
		WorktimeLiveFeedManager $liveFeedManager
	)
	{
		$this->worktimeManagerFactory = $recordFactory;
		$this->worktimeAgentManager = $worktimeAgentManager;
		$this->actionList = $actionList;
		$this->worktimeRepository = $worktimeRepository;
		$this->notificationService = $notificationService;
		$this->liveFeedManager = $liveFeedManager;
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @return Result|WorktimeServiceResult
	 */
	public function startWorktime($recordForm)
	{
		$this->recordForm = clone $recordForm;
		if ($this->recordForm->getFirstEventName() === null)
		{
			$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_START;
			if ($this->recordForm->recordedStartSeconds !== null ||
				$this->recordForm->recordedStartTimestamp !== null ||
				$this->recordForm->recordedStartTime !== null
			)
			{
				$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_START_WITH_ANOTHER_TIME;
			}
		}
		return $this->processWorktimeAction($this->recordForm,
			function () {
				$recordStartDate = null;
				if ($this->recordForm->userId > 0 &&
					$this->recordForm->getFirstEventName() === WorktimeEventTable::EVENT_TYPE_START_WITH_ANOTHER_TIME)
				{
					$recordStartDate = $this->recordForm->buildStartTimestampBySecondsAndDate($this->recordForm->userId);
					if ($recordStartDate > 0)
					{
						$recordStartDate = TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $recordStartDate, $this->recordForm->userId);
					}
				}
				return $this->checkActionEligibility(
					$this->buildActionList($this->recordForm->userId, $recordStartDate)->getStartActions()
				);
			}
		);
	}

	public function continueWork($recordForm)
	{
		$this->recordForm = clone $recordForm;
		if ($this->recordForm->getFirstEventName() === null)
		{
			$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_CONTINUE;
		}
		return $this->processWorktimeAction($this->recordForm,
			function () use ($recordForm) {
				$actionList = $this->buildActionList($recordForm->userId);
				$continueActions = array_merge(
					$actionList->getContinueActions(),
					$actionList->getReopenActions()
				);
				if (empty($continueActions))
				{
					$continueActions[] = WorktimeAction::createContinueAction($recordForm->userId);
					$this->actionList->fillActions($continueActions);
				}
				return $this->checkActionEligibility($continueActions);
			}
		);
	}

	public function pauseWork($recordForm)
	{
		$this->recordForm = clone $recordForm;
		if ($this->recordForm->getFirstEventName() === null)
		{
			$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_PAUSE;
		}
		return $this->processWorktimeAction($this->recordForm,
			function () use ($recordForm) {
				$pauseActions = $this->buildActionList($recordForm->userId)->getPauseActions();
				if (empty($pauseActions))
				{
					$pauseActions[] = WorktimeAction::createPauseAction($recordForm->userId);
					$this->actionList->fillActions($pauseActions);
				}
				return $this->checkActionEligibility($pauseActions);
			}
		);
	}

	public function stopWorktime($recordForm)
	{
		$this->recordForm = clone $recordForm;
		if ($this->recordForm->getFirstEventName() === null)
		{
			$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_STOP;
			if ($this->recordForm->recordedStopSeconds !== null ||
				$this->recordForm->recordedStopTimestamp !== null ||
				$this->recordForm->recordedStopTime !== null
			)
			{
				$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_STOP_WITH_ANOTHER_TIME;
			}
		}
		return $this->processWorktimeAction($this->recordForm,
			function () use ($recordForm) {
				$stopActions = $this->buildActionList($recordForm->userId)->getStopActions();
				if (empty($stopActions))
				{
					$stopActions[] = WorktimeAction::createStopAction($recordForm->userId);
					$this->actionList->fillActions($stopActions);
				}
				return $this->checkActionEligibility($stopActions);
			}
		);
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @return WorktimeServiceResult
	 */
	public function editWorktime($recordForm)
	{
		$this->recordForm = clone $recordForm;
		if ($this->recordForm->getFirstEventName() === null)
		{
			$this->recordForm->getFirstEventForm()->eventName = WorktimeEventTable::EVENT_TYPE_EDIT_WORKTIME;
		}
		return $this->processWorktimeAction($this->recordForm,
			function () use ($recordForm) {
				$editActions = $this->buildActionList($recordForm->userId)->getEditActions();
				if (empty($editActions))
				{
					$editActions[] = WorktimeAction::createEditAction($recordForm->userId);
					$this->actionList->fillActions($editActions);
				}
				return $this->checkActionEligibility($editActions);
			}
		);
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @return BaseServiceResult|WorktimeServiceResult
	 */
	public function approveWorktimeRecord($recordForm)
	{
		$this->recordForm = $recordForm;
		$record = $this->worktimeRepository->findByIdWith($recordForm->id, ['SCHEDULE', 'SCHEDULE.SCHEDULE_VIOLATION_RULES', 'SHIFT']);
		if (!$record)
		{
			return WorktimeServiceResult::createWithErrorText(
				Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_WORKTIME_RECORD_NOT_FOUND')
			);
		}

		return $this->processWorktimeAction($this->recordForm,
			function () use ($recordForm, $record) {
				return $this->checkActionEligibility(
					[
						$this->actionList->buildApproveAction(
							$record,
							$record->obtainSchedule(),
							$record->obtainShift(),
							TimeHelper::getInstance()->getUserDateTimeNow($record->getUserId())
						),
					]
				);
			}
		);
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @param $checkActionCallback
	 * @return WorktimeServiceResult|BaseServiceResult
	 */
	private function processWorktimeAction($recordForm, $checkActionCallback)
	{
		return $this->wrapAction(function () use ($recordForm, $checkActionCallback) {
			/** @var WorktimeServiceResult $actionListResult */
			$this->safeRun($actionListResult = $checkActionCallback());

			$this->safeRun(
				$buildingRecordResult = $this->getWorktimeManager()
					->buildActualRecord($actionListResult->getWorktimeAction(), $this->worktimeRepository)
			);

			$actualRecord = $buildingRecordResult->getWorktimeRecord();
			if (empty($actualRecord->collectValues(Values::CURRENT)))
			{
				return (new WorktimeServiceResult())
					->setSchedule($actionListResult->getSchedule())
					->setShift($actionListResult->getShift())
					->setWorktimeRecord($actualRecord);
			}

			$worktimeEvents = $this->getWorktimeManager()->buildEvents($actualRecord);
			$this->runBeforeRecordSave($actualRecord);

			$this->safeRun($this->save($actualRecord, $worktimeEvents));
			// we need ID, so we save and then updating if needed
			$this->runAfterRecordSave($actualRecord, $actionListResult->getSchedule(), $actionListResult->getShift(), $recordForm->getFirstEventName());

			if ($actionListResult->getSchedule())
			{
				$this->sendNotifications($actualRecord, $actionListResult->getSchedule());
			}

			return (new WorktimeServiceResult())
				->setWorktimeRecord($actualRecord)
				->setSchedule($actionListResult->getSchedule())
				->setShift($actionListResult->getShift())
				->setWorktimeEvents($worktimeEvents);
		});
	}

	/**
	 * @param int $userId
	 * @return WorktimeActionList
	 */
	private function buildActionList($userId, $userDate = null)
	{
		(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable::getEntity())->cleanCache();

		return $this->actionList->buildPossibleActionsListForUser($userId, $userDate);
	}

	protected function wrapResultOnException($result)
	{
		return WorktimeServiceResult::createByResult($result);
	}

	/**
	 * @param WorktimeRecord $record
	 * @param WorktimeEvent[] $worktimeEvents
	 * @return Result
	 */
	protected function save($record, $worktimeEvents)
	{
		$res = $this->worktimeRepository->save($record);
		if (!$res->isSuccess())
		{
			foreach ($res->getErrors() as $error)
			{
				if ($error->getCode() === WorktimeEventsManager::ERROR_CODE_CANCEL)
				{
					return WorktimeServiceResult::createWithErrorText('cannot perform this action because it has been canceled by event handler');
				}
			}
			return WorktimeServiceResult::createByResult($res);
		}
		foreach ($worktimeEvents as $worktimeEvent)
		{
			/** @var WorktimeRecordIdStorable $worktimeEvent */
			$worktimeEvent->setRecordId($record->getId());
			$res = $this->worktimeRepository->save($worktimeEvent);
			if (!$res->isSuccess())
			{
				return WorktimeServiceResult::createByResult($res);
			}
		}

		return new WorktimeServiceResult();
	}

	private function sendNotifications(WorktimeRecord $worktimeRecord, Schedule $schedule)
	{
		$this->getWorktimeManager()->notifyOfActionOldStyle($worktimeRecord, $schedule);

		$violations = $this->getWorktimeManager()->buildRecordViolations($worktimeRecord, $schedule);
		$this->notificationService->sendViolationsNotifications(
			$schedule,
			$violations,
			$worktimeRecord
		);
	}

	/**
	 * @param $recordForm
	 * @param $actions
	 * @return WorktimeServiceResult
	 */
	private function checkActionEligibility($actions)
	{
		$result = new WorktimeServiceResult();
		if (empty($actions))
		{
			return $result->addProhibitedActionError(
				WorktimeServiceResult::ERROR_FOR_USER,
				WorktimeServiceResult::ERROR_EMPTY_ACTIONS
			);
		}

		/** @var WorktimeAction $action */
		$action = end($actions);
		if (count($actions) > 1)
		{
			if ($this->recordForm->id)
			{
				/** @var WorktimeAction $availableAction */
				foreach ($actions as $availableAction)
				{
					$actionRecord = $availableAction->getRecord();
					if ($actionRecord->getId() == $this->recordForm->id)
					{
						$action = $availableAction;
					}
				}
			}
		}

		if ($action->getRecord() && !$action->getRecordManager())
		{
			return $result->addError(new Error('WorktimeAction must have WorktimeRecordManager instance ' . __FILE__ . ':' . __LINE__));
		}

		$result->setShift($action->getShift());
		$result->setSchedule($action->getSchedule());
		$result->setWorktimeRecord($action->getRecord());
		$result->setWorktimeAction($action);
		return $result;
	}

	/**
	 * @return Record\WorktimeManager
	 */
	private function getWorktimeManager()
	{
		if ($this->worktimeManager === null)
		{
			if (!$this->recordForm)
			{
				throw new ObjectException(WorktimeRecordForm::class . ' is required');
			}
			$this->worktimeManager = $this->worktimeManagerFactory->buildManager($this->recordForm);
		}
		return $this->worktimeManager;
	}

	private function runAfterRecordSave(WorktimeRecord $record, ?Schedule $schedule, ?Shift $shift, $eventType)
	{
		if ($schedule && $schedule->isAutoClosing() &&
			$record->getRecordedStopTimestamp() === 0 &&
			$record->getAutoClosingAgentId() === 0)
		{
			switch ($eventType)
			{
				case WorktimeEventTable::EVENT_TYPE_START:
				case WorktimeEventTable::EVENT_TYPE_EDIT_START:
				case WorktimeEventTable::EVENT_TYPE_START_WITH_ANOTHER_TIME:
					$this->worktimeAgentManager->createAutoClosingAgent($record, $schedule, $shift);
					break;
				default:
					break;
			}
		}
		if (!empty($record->collectValues(Values::CURRENT)))
		{
			$this->worktimeRepository->save($record);
		}
	}

	private function runBeforeRecordSave(WorktimeRecord $actualRecord)
	{
		$this->getWorktimeManager()->onBeforeRecordSave($actualRecord, $this->liveFeedManager);
	}
}