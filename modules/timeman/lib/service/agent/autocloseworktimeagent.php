<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
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
		$record = $this->worktimeRepository->findByIdWith($recordId, ['SCHEDULE', 'SHIFT']);
		if (!$record || $record->getRecordedStopTimestamp() > 0 ||
			!$record->obtainSchedule() || !$record->obtainSchedule()->isAutoClosing())
		{
			return '';
		}
		$manager = DependencyManager::getInstance()
			->buildWorktimeRecordManager(
				$record,
				$record->obtainSchedule(),
				$record->obtainShift()
			);
		$recordStopUtcTimestamp = $manager->buildStopTimestampForAutoClose();
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

		if (\Bitrix\Timeman\Integration\Stafftrack\CheckIn::isCheckInStartEnabled())
		{
			$recordForm->getFirstEventForm()->reason = Loc::getMessage('TIMEMAN_CHECK_IN_CLOSE_DAY_REASON');
		}

		$result = $this->worktimeService->stopWorktime($recordForm);
		if (
			\Bitrix\Timeman\Integration\Stafftrack\CheckIn::isCheckInStartEnabled()
			&& $result->isSuccess()
		)
		{
			$reportData = [];
			$queryObject = \CTimeManReport::getList(
				[],
				[
					'ENTRY_ID' => $record->getId(),
					'REPORT_TYPE' => 'REPORT',
				],
			);
			if ($report = $queryObject->fetch())
			{
				$reportData['REPORT'] = $report['REPORT'];
			}

			\CTimeManReportDaily::Add([
				'USER_ID' => $record->getUserId(),
				'ENTRY_ID' => $record->getId(),
				'REPORT_DATE' => $record->getDateStart()->setTime(0, 0),
				'ACTIVE' => $record->getActive() ? 'Y' : 'N',
				'REPORT' => $reportData['REPORT'] ?? '',
			], true);
		}

		return '';
	}

}