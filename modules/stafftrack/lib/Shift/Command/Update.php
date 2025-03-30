<?php

namespace Bitrix\StaffTrack\Shift\Command;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Stafftrack\Integration\Pull;
use Bitrix\StaffTrack\Internals\Exception\InvalidDtoException;
use Bitrix\StaffTrack\Model\ShiftTable;
use Bitrix\StaffTrack\Provider\ShiftProvider;
use Bitrix\StaffTrack\Shift\Observer;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Update extends AbstractCommand
{
	/**
	 * @param ShiftDto $shiftDto
	 *
	 * @return Result
	 * @throws InvalidDtoException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public function execute(ShiftDto $shiftDto): Result
	{
		$result = new Result();

		$this->shiftDto = $shiftDto;

		$this->shiftDto->validateUpdate();

		$changes = $this->getChanges($this->shiftDto);

		if (!empty($changes))
		{
			$updateResult = ShiftTable::update($shiftDto->id, $changes);
			if (!$updateResult->isSuccess())
			{
				return $result->addErrors($updateResult->getErrors());
			}
		}

		try
		{
			$this->notify(...$this->observers);
		}
		catch (\Throwable $exception)
		{
			$result->addError(Error::createFromThrowable($exception));
		}

		$this->sendPushToDepartment(Pull\PushCommand::SHIFT_UPDATE);

		return $result->setData([
			'shift' => $this->shiftDto,
		]);
	}

	/**
	 * @param ShiftDto $shiftDto
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getChanges(ShiftDto $shiftDto): array
	{
		$shiftEntityBeforeUpdate = ShiftProvider::getInstance($shiftDto->userId)->get($shiftDto->id);

		if (!$shiftEntityBeforeUpdate)
		{
			return [];
		}

		$oldValues = $shiftEntityBeforeUpdate->toArray();
		$newValues = array_intersect_key($shiftDto->toArray(), $oldValues);

		return array_diff_assoc($newValues, $oldValues);
	}

	/**
	 * @return void
	 */
	protected function init(): void
	{
		parent::init();

		$this
			->addObserver(new Observer\Message\Update())
			->addObserver(new Observer\Cancellation\Update())
		;
	}
}
