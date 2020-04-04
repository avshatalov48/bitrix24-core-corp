<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\WorktimeService;

Loc::loadMessages(__FILE__);

class AutoCloseWorktimeAgent
{
	/** @var WorktimeRepository */
	private $worktimeRepository;
	private $worktimeService;

	public function __construct(WorktimeRepository $worktimeRepository, WorktimeService $worktimeService)
	{
		$this->worktimeRepository = $worktimeRepository;
		$this->worktimeService = $worktimeService;
	}

	public static function runCloseRecord($recordId)
	{
		return DependencyManager::getInstance()
			->getAutoCloseWorktimeAgent()
			->closeRecord($recordId);
	}

	public function closeRecord($recordId)
	{
		$record = $this->worktimeRepository->findByIdWith($recordId, ['SCHEDULE', 'SHIFT', 'SCHEDULE.SHIFTS']);
		if (!$record || !$record->obtainSchedule() || !$record->obtainSchedule()->isAutoClosing()
			|| $record->getRecordedStopTimestamp() > 0)
		{
			return '';
		}
		$schedule = $record->obtainSchedule();
		$recordStopUtcTimestamp = $record->buildStopTimestampForAutoClose($schedule, $record->obtainShift());
		if ($recordStopUtcTimestamp === null)
		{
			return '';
		}
		$recordStop = TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $recordStopUtcTimestamp, $record->getUserId());
		if (!$recordStop)
		{
			return '';
		}
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->recordedStopSeconds = TimeHelper::getInstance()->getSecondsFromDateTime($recordStop);
		$recordForm->recordedStopDateFormatted = \Bitrix\Main\Type\Date::createFromPhp($recordStop)->toString();
		$recordForm->userId = $record->getUserId();
		$recordForm->isSystem = true;
		$recordForm->stopOffset = $record->getStartOffset();
		$this->worktimeService->stopWorktime($recordForm);

		return '';
	}

}