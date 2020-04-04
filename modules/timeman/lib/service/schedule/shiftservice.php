<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Timeman\Form\Schedule\ShiftForm;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ShiftServiceResult;

class ShiftService extends BaseService
{
	/** @var ShiftRepository */
	private $shiftRepository;

	public function __construct(ShiftRepository $shiftRepository)
	{
		$this->shiftRepository = $shiftRepository;
	}

	/**
	 * @param ShiftForm $shiftForm
	 * @return ShiftServiceResult
	 */
	public function add($scheduleOrId, ShiftForm $shiftForm)
	{
		$schedule = $scheduleOrId;
		if (!($schedule instanceof Schedule))
		{
			if (!($schedule = $this->shiftRepository->findScheduleById($scheduleOrId)))
			{
				return (new ShiftServiceResult())->addScheduleNotFoundError();
			}
		}
		$shift = Shift::create(
			$schedule->getId(),
			$shiftForm->name,
			$shiftForm->startTime,
			$shiftForm->endTime,
			$shiftForm->breakDuration,
			$shiftForm->workDays
		);
		$saveResult = $this->shiftRepository->save($shift);
		if (!$saveResult->isSuccess())
		{
			return ShiftServiceResult::createByResult($saveResult);
		}

		return (new ShiftServiceResult())->setShift($shift);
	}

	/**
	 * @param $shift
	 * @param ShiftForm $shiftForm
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|ShiftServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update($shiftOrId, ShiftForm $shiftForm)
	{
		$shift = $shiftOrId;
		if (!($shift instanceof Shift))
		{
			$shift = $this->shiftRepository->findByIdWithSchedule((int)$shiftOrId);
		}
		if (!$shift)
		{
			return (new ShiftServiceResult())->addShiftNotFoundError();
		}

		$shift->edit(
			$shiftForm->name,
			$shiftForm->startTime,
			$shiftForm->endTime,
			$shiftForm->breakDuration,
			$shiftForm->workDays
		);
		$res = $this->shiftRepository->save($shift);
		if ($res->isSuccess())
		{
			return (new ShiftServiceResult())->setShift($shift);
		}
		return ShiftServiceResult::createByResult($res);
	}

	public function deleteShiftById($removedShiftId)
	{
		return $this->shiftRepository->markShiftDeleted($removedShiftId);
	}
}