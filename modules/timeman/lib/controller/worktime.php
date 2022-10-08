<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\UseCase\Worktime\Manage;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Main\Engine\Controller;

class Worktime extends Controller
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$restOnlyScope = new Scope(Scope::REST);
		$actionsForRest = [
			'start',
			'stop',
			'relaunch',
			'pause',
		];
		foreach ($actionsForRest as $name)
		{
			$configureActions[$name] = [
				'-prefilters' => [Scope::class],
				'+prefilters' => [$restOnlyScope,],
			];
		}

		return $configureActions;
	}

	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new Scope(Scope::AJAX),
			]
		);
	}

	public function pauseAction($userId, $latitudeClose = null, $longitudeClose = null, $ipClose = null, $device = null)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->userId = $userId;
		$recordForm->latitudeClose = $latitudeClose;
		$recordForm->longitudeClose = $longitudeClose;
		$recordForm->ipClose = $ipClose;
		$recordForm->device = $device;

		if (!$recordForm->validate())
		{
			return $recordForm->getErrors();
		}

		$result = (new Manage\Pause\Handler())->handle($recordForm);
		return $this->decorateServiceResult($result);
	}

	public function startAction($userId, $startSeconds = null, $startDate = null, $reason = null, $latitudeOpen = null, $longitudeOpen = null, $ipOpen = null, $device = null)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->userId = $userId;
		$recordForm->recordedStartSeconds = $startSeconds;
		$recordForm->getFirstEventForm()->reason = $reason;
		$recordForm->latitudeOpen = $latitudeOpen;
		$recordForm->longitudeOpen = $longitudeOpen;
		$recordForm->ipOpen = $ipOpen;
		$recordForm->device = $device;
		$recordForm->recordedStartDateFormatted = $startDate;

		if (!$recordForm->validate())
		{
			return $recordForm->getErrors();
		}
		$result = (new Manage\Start\Handler())->handle($recordForm);
		return $this->decorateServiceResult($result);
	}

	public function stopAction($userId, $stopSeconds = null, $stopDate = null, $reason = null, $latitudeClose = null, $longitudeClose = null, $ipClose = null, $device = null)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->userId = $userId;
		$recordForm->recordedStopSeconds = $stopSeconds;
		$recordForm->getFirstEventForm()->reason = $reason;
		$recordForm->latitudeClose = $latitudeClose;
		$recordForm->longitudeClose = $longitudeClose;
		$recordForm->ipClose = $ipClose;
		$recordForm->device = $device;
		$recordForm->recordedStopDateFormatted = $stopDate;

		if (!$recordForm->validate())
		{
			return $recordForm->getErrors();
		}
		$result = (new Manage\Stop\Handler())->handle($recordForm);
		return $this->decorateServiceResult($result);
	}

	public function relaunchAction($userId, $device = null)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->userId = $userId;
		$recordForm->device = $device;

		if (!$recordForm->validate())
		{
			return $recordForm->getErrors();
		}
		$result = (new Manage\Relaunch\Handler())->handle($recordForm);
		return $this->decorateServiceResult($result);
	}

	##### AJAX

	public function approveRecordAction()
	{
		$worktimeForm = WorktimeRecordForm::createWithEventForm(WorktimeEventTable::EVENT_TYPE_APPROVE);
		$worktimeForm->approvedBy = $this->getCurrentUser()->getId();
		$worktimeForm->load($this->getRequest());

		if ($worktimeForm->validate())
		{
			$oldStart = WorktimeRecordTable::query()
				->addSelect('ID')
				->addSelect('RECORDED_START_TIMESTAMP')
				->where('ID', $worktimeForm->id)
				->exec()
				->fetchObject();
			$result = (new Manage\Approve\Handler())->handle($worktimeForm);
			if (WorktimeServiceResult::isSuccessResult($result))
			{
				return $this->makeAjaxResult($result, $worktimeForm, $oldStart);
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($worktimeForm->getFirstError());
		return [];
	}

	public function changeRecordAction()
	{
		$worktimeForm = WorktimeRecordForm::createWithEventForm(WorktimeEventTable::EVENT_TYPE_EDIT_WORKTIME);
		$worktimeForm->load($this->getRequest());

		if ($worktimeForm->validate())
		{
			$workTimeRepository = new WorktimeRepository();

			$record = $workTimeRepository->findById($worktimeForm->id);

			if ($record)
			{
				$worktimeForm->editedBy = $this->getCurrentUser()->getId();

				$record->updateByForm($worktimeForm);

				$result = $workTimeRepository->save($record);
				if (!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());

					return [];
				}

				$workTimeRepository->save(
					WorktimeEvent::create(
						WorktimeEventTable::EVENT_TYPE_STOP_WITH_ANOTHER_TIME,
						$record->getUserId(),
						$record->getId(),
						null,
						Loc::getMessage('TIMEMAN_EXPIRED_REPORT_MESSAGE'),
						$worktimeForm->device
					)
				);
			}

			$actualRecord = WorktimeRecordTable::query()
				->addSelect('*')
				->where('ID', $record->getId())
				->exec()
				->fetchObject()
			;

			$result = new WorktimeServiceResult();
			$result->setWorktimeRecord($actualRecord);
			if (WorktimeServiceResult::isSuccessResult($result))
			{
				return $this->makeAjaxResult($result, $worktimeForm, $record);
			}

			$this->addErrors($result->getErrors());

			return [];
		}

		$this->addError($worktimeForm->getFirstError());

		return [];
	}

	/**
	 * @param WorktimeServiceResult $serviceResult
	 * @param WorktimeRecordForm $worktimeForm
	 * @param WorktimeRecord $oldRecord
	 * @return array
	 */
	private function makeAjaxResult($serviceResult, $worktimeForm, $oldRecord)
	{
		/** @var array $result */
		$result = $this->convertKeysToCamelCase($serviceResult->getWorktimeRecord()->collectValues());

		$updatedRecord = $serviceResult->getWorktimeRecord();
		$startTimestampBefore = $oldRecord ? $oldRecord->getRecordedStartTimestamp() : $updatedRecord->getRecordedStartTimestamp();
		$startTimestampAfter = $updatedRecord->getRecordedStartTimestamp();
		if ($worktimeForm->useEmployeesTimezone)
		{
			$offset = $updatedRecord->getStartOffset();
		}
		else
		{
			$offset = TimeHelper::getInstance()->getUserUtcOffset($this->getCurrentUser()->getId());
		}
		$dateBefore = TimeHelper::getInstance()->createDateTimeFromFormat('U', $startTimestampBefore, $offset);
		$dateAfter = TimeHelper::getInstance()->createDateTimeFromFormat('U', $startTimestampAfter, $offset);
		$dates = [];
		$dates[$dateBefore->format('d.m.Y')] = $dateBefore;
		$dates[$dateAfter->format('d.m.Y')] = $dateAfter;
		global $APPLICATION;
		$result['dayCellsHtml'] = [];
		foreach ($dates as $date)
		{
			ob_start();
			$APPLICATION->includeComponent(
				'bitrix:timeman.worktime.grid',
				'.default',
				[
					'PARTIAL_ITEM' => 'shiftCell',
					'DRAWING_TIMESTAMP' => $date->getTimestamp(),
					'INCLUDE_CSS' => false,
					'IS_SHIFTPLAN' => $this->getRequest()->get('isShiftplan') === 'true',
					'USER_ID' => $serviceResult->getWorktimeRecord()->getUserId(),
				]
			);
			$result['dayCellsHtml'][] = ob_get_clean();
		}

		return ['record' => $result];
	}

	private function decorateServiceResult(WorktimeServiceResult $result)
	{
		if (!$result->isSuccess())
		{
			$error = $result->getFirstError();
			if ($error && !empty($error->getCustomData()) && !empty($error->getCustomData()['reasonCode']))
			{
				$code = $error->getCustomData()['reasonCode'];
				if ($code === WorktimeServiceResult::ERROR_EMPTY_ACTIONS)
				{
					return new Error('Can not perform such an action for this user');
				}
				return new Error('', $code);
			}
			return $result->getErrors();
		}
		return $this->convertKeysToCamelCase($result->getWorktimeRecord()->collectRawValues());
	}
}