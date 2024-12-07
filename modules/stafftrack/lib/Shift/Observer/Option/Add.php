<?php

namespace Bitrix\StaffTrack\Shift\Observer\Option;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\StaffTrack\Dictionary\Location;
use Bitrix\StaffTrack\Dictionary\Option;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Provider\OptionProvider;
use Bitrix\StaffTrack\Service\OptionService;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add implements ObserverInterface
{
	private OptionService $service;
	private OptionProvider $provider;
	private ShiftDto $shiftDto;

	public function __construct()
	{
		$this->provider = OptionProvider::getInstance();
		$this->service = OptionService::getInstance();
	}

	/**
	 * @param ShiftDto $shiftDto
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(ShiftDto $shiftDto): void
	{
		$this->shiftDto = $shiftDto;

		$this->saveOption(
			Option::TIMEZONE_OFFSET,
			$this->shiftDto->timezoneOffset
		);

		if ($this->shiftDto->skipOptions === true)
		{
			return;
		}

		$this->saveStateOption();

		if (!empty($this->shiftDto->dialogId))
		{
			$this->saveOption(
				Option::LAST_SELECTED_DIALOG_ID,
				$this->shiftDto->dialogId
			);
		}

		if (!empty($this->shiftDto->message))
		{
			$this->saveOption(
				Option::DEFAULT_MESSAGE,
				$this->shiftDto->message
			);
		}

		if (!empty($this->shiftDto->location))
		{
			if (Location::tryFrom($this->shiftDto->location))
			{
				$this->saveOption(
					Option::DEFAULT_LOCATION,
					$this->shiftDto->location
				);
			}
			else
			{
				$this->saveOption(
					Option::DEFAULT_LOCATION,
					Location::CUSTOM->value,
				);

				$this->saveOption(
					Option::DEFAULT_CUSTOM_LOCATION,
					$this->shiftDto->location,
				);
			}
		}

		$this->provider->invalidateCache($this->shiftDto->userId);
	}

	/**
	 * @return void
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function saveStateOption(): void
	{
		if (Status::isWorkingStatus($this->shiftDto->status))
		{
			$this->saveOption(
				Option::SEND_MESSAGE,
				!empty($this->shiftDto->message) ? 'Y' : 'N',
			);
		}

		$this->saveOption(
			Option::SEND_GEO,
			!empty($this->shiftDto->address) && !empty($this->shiftDto->geoImageUrl) ? 'Y' : 'N',
		);
	}

	/**
	 * @param Option $optionEnum
	 * @param string $value
	 * @return void
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function saveOption(Option $optionEnum, string $value): void
	{
		$option = $this->provider->getOption($this->shiftDto->userId, $optionEnum);

		if ($option !== null && $option->getValue() === $value)
		{
			return;
		}

		$this->service->save($this->shiftDto->userId, $optionEnum, $value);
	}
}