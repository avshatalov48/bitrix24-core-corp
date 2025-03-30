<?php

namespace Bitrix\StaffTrack\Shift\Command;

use Bitrix\Main;
use Bitrix\Stafftrack\Integration\Pull;
use Bitrix\StaffTrack\Internals\Exception\InvalidDtoException;
use Bitrix\StaffTrack\Shift\Observer;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add extends AbstractCommand
{
	/**
	 * @param ShiftDto $shiftDto
	 * @return Main\Result
	 * @throws InvalidDtoException
	 */
	public function execute(ShiftDto $shiftDto): Main\Result
	{
		$result = new Main\Result();

		$this->shiftDto = clone $shiftDto->setId(0);

		$this->shiftDto->validateAdd();

		$shift = $this->mapper->createEntityFromDto($this->shiftDto);

		$createResult = $shift->save();
		if (!$createResult->isSuccess())
		{
			return $result->addErrors($createResult->getErrors());
		}

		$this->shiftDto->id = $createResult->getId();
		try
		{
			$this->notify(...$this->observers);
		}
		catch (\Throwable $exception)
		{
			$result->addError(Main\Error::createFromThrowable($exception));
		}

		$this->sendPushToDepartment(Pull\PushCommand::SHIFT_ADD);

		return $result->setData([
			'shift' => $this->shiftDto,
		]);
	}

	/**
	 * @return void
	 */
	protected function init(): void
	{
		parent::init();

		$this
			->addObserver(new Observer\Message\Add())
			->addObserver(new Observer\Option\Add())
			->addObserver(new Observer\WorkDay\Add())
			->addObserver(new Observer\Counter\Add())
			->addObserver(new Observer\Geo\Add())
			->addObserver(new Observer\Cancellation\Add())
		;
	}
}
