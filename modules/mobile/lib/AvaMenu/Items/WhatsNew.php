<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\WhatsNewFeature;
use Bitrix\MobileApp\Mobile;

class WhatsNew extends AbstractMenuItem
{
	public const MINIMAL_API_VERSION = 60;

	public function isAvailable(): bool
	{
		return (
			Feature::isEnabled(WhatsNewFeature::class)
			&& Mobile::getApiVersion() >= self::MINIMAL_API_VERSION
		);
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'counter' => 0,
			'customData' => $this->getEntryParams(),
		];
	}

	public function getId(): string
	{
		return 'whats_new';
	}

	public function getIconId(): string
	{
		return 'favorite';
	}

	private function getEntryParams(): array
	{
		return [
			'title' => Loc::getMessage('AVA_MENU_NAME_WHATS_NEW'),
			'type' => 'component',
			'name' => 'JSStackComponent',
			'componentCode' => 'whats.new',
			'scriptPath' => Manager::getComponentPath('what.new'),
			'params' => [],
		];
	}
}
