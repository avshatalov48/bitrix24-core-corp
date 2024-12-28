<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;
use Bitrix\SignMobile\Config\Feature;

class Signing extends AbstractMenuItem
{

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public function isAvailable(): bool
	{
		return
			Loader::includeModule('signmobile')
			&& class_exists(Feature::class)
			&& method_exists(Feature::class, 'isMyDocumentsGridAvailable')
			&& Feature::instance()->isMyDocumentsGridAvailable()
		;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'customData' => $this->getEntryParams(),
		];
	}

	private function getEntryParams(): array
	{
		return [
			'showHint' => false,
		];
	}

	public function getId(): string
	{
		return 'start_signing';
	}

	public function getIconId(): string
	{
		return 'sign';
	}
}
