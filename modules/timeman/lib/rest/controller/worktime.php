<?php
namespace Bitrix\Timeman\Rest\Controller;

use Bitrix\Main\Text\StringHelper;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\UseCase\Worktime\Manage;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Main\Engine\Controller;

class Worktime extends Controller
{
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
				return $this->makeResult($result, $worktimeForm, $oldStart);
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
	private function makeResult($serviceResult, $worktimeForm, $oldRecord)
	{
		$baseValues = $serviceResult->getWorktimeRecord()->collectValues();
		$result = [];
		foreach ($baseValues as $snakeCaseName => $baseValue)
		{
			$result[lcfirst(StringHelper::snake2camel($snakeCaseName))] = $baseValue;
		}

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
}