<?php
namespace Bitrix\Timeman\Rest\Controller;

use Bitrix\Main\Text\StringHelper;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
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
			$result = (new Manage\Approve\Handler())->handle($worktimeForm);
			if (WorktimeServiceResult::isSuccessResult($result))
			{
				return $this->makeResult($result);
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($worktimeForm->getFirstError());
		return [];
	}

	/**
	 * @param WorktimeServiceResult $serviceResult
	 * @return array
	 */
	private function makeResult($serviceResult)
	{
		$baseValues = $serviceResult->getWorktimeRecord()->collectValues();
		$result = [];
		foreach ($baseValues as $snakeCaseName => $baseValue)
		{
			$result[lcfirst(StringHelper::snake2camel($snakeCaseName))] = $baseValue;
		}
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:timeman.worktime.grid',
			'.default',
			[
				'PARTIAL' => true,
				'PARTIAL_ITEM' => 'shiftCell',
				'DRAWING_DATE' => TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $serviceResult->getWorktimeRecord()->getRecordedStartTimestamp(), $this->getCurrentUser()->getId()),
				'SHOW_ADD_SHIFT_PLAN_BTN' => false,
				'INCLUDE_CSS' => false,
				'IS_SHIFTED_SCHEDULE' => \Bitrix\Timeman\Model\Schedule\Schedule::isScheduleShifted($serviceResult->getWorktimeRecord()->obtainSchedule()),
				'SCHEDULE_ID' => $serviceResult->getWorktimeRecord()->getScheduleId(),
				'SHIFT_PLAN' => [],
				'WORKTIME_RECORD' => $serviceResult->getWorktimeRecord()->collectValues(),
				'USER_ID' => $serviceResult->getWorktimeRecord()->getUserId(),
			]
		);
		$result['recordCellHtml'] = ob_get_clean();
		return ['record' => $result];
	}
}