<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;
use Bitrix\StaffTrack\Feature;
use Bitrix\StaffTrack\Provider\CounterProvider;

class CheckIn extends AbstractMenuItem
{
	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public function isAvailable(): bool
	{
		return $this->isModuleIncluded()
			&& !($this->context->extranet || $this->context->isCollaber)
			&& Feature::isCheckInEnabled()
		;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'counter' => $this->getCounter(),
			'customData' => $this->getCustomData(),
		];
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return 'check_in';
	}

	/**
	 * @return string
	 */
	public function getIconId(): string
	{
		return 'play';
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isModuleIncluded(): bool
	{
		return Loader::includeModule('stafftrack')
			&& Loader::includeModule('stafftrackmobile')
		;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getCounter(): string
	{
		return CounterProvider::getInstance()->isNeededToShow($this->context->userId) ? '1' : '';
	}

	/**
	 * @return string[]
	 */
	private function getCustomData(): array
	{
		return [
			'enabledBySettings' => Feature::isCheckInEnabledBySettings(),
		];
	}
}
