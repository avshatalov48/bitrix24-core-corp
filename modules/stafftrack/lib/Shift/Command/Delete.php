<?php

namespace Bitrix\StaffTrack\Shift\Command;

use Bitrix\Main;
use Bitrix\Stafftrack\Integration\Pull;
use Bitrix\StaffTrack\Internals\Exception\InvalidDtoException;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Delete extends AbstractCommand
{
	/**
	 * @param ShiftDto $shiftDto
	 * @return Main\Result
	 * @throws InvalidDtoException
	 */
	public function execute(ShiftDto $shiftDto): Main\Result
	{
		$result = new Main\Result();

		$this->shiftDto = $shiftDto;

		$this->shiftDto->validateDelete();

		$shift = $this->mapper->createEntityFromDto($this->shiftDto);

		$deleteResult = $shift->delete();
		if (!$deleteResult->isSuccess())
		{
			return $result->addErrors($deleteResult->getErrors());
		}

		try
		{
			$this->notify(...$this->observers);
		}
		catch (\Throwable $exception)
		{
			$result->addError(Main\Error::createFromThrowable($exception));
		}

		$this->sendPushToDepartment(Pull\PushCommand::SHIFT_DELETE);

		return $result;
	}
}