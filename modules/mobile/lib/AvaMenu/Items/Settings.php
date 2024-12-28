<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;
use Bitrix\MobileApp\Janative\Manager;

class Settings extends AbstractMenuItem
{
	public function isAvailable(): bool
	{
		return true;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'customData' => $this->getEntryParams(),
		];
	}

	public function getId(): string
	{
		return 'settings';
	}

	public function getIconId(): string
	{
		return 'settings';
	}

	private function getEntryParams(): array
	{
		global $USER;

		$settingsUserId = $USER->GetID();
		$isUserAdmin = ($USER->isAdmin() || (\CModule::IncludeModule('bitrix24') && \CBitrix24::isPortalAdmin($settingsUserId)));

		return [
			'type' => 'component',
			'scriptPath' => Manager::getComponentPath('settings'),
			'componentCode' => 'settings.config',
			'params' => [
				'USER_ID' => $settingsUserId,
				'SITE_ID' => SITE_ID,
				'LANGUAGE_ID' => LANGUAGE_ID,
				'IS_ADMIN' => $isUserAdmin,
			],
			'rootWidget' => [
				'name' => 'settings',
				'settings' => [
					'objectName' => 'settings',
					'titleParams' => [
						'text' => Loc::getMessage('MENU_SETTINGS'),
						'type' => 'section',
					],
				]
			],
		];
	}

	public function separatorBefore(): bool
	{
		return !$this->context->isCollaber;
	}
}
