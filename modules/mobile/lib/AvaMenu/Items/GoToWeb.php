<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class GoToWeb extends AbstractMenuItem
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
			'counter' => $this->getCounter(),
		];
	}

	public function getId(): string
	{
		return 'go_to_web';
	}

	public function getIconId(): string
	{
		return 'go_to';
	}

	public function getMessageCode(): string
	{
		return 'AVA_MENU_NAME_GO_TO_WEB_MSGVER_1';
	}

	private function getEntryParams(): array
	{
		return [
			'type' => 'qrauth',
			'title' => Loc::getMessage('MOBILE_AVA_MENU_GO_TO_WEB_TITLE'),
			'showHint' => true,
			'hintText' => Loc::getMessage('MOBILE_AVA_MENU_GO_TO_WEB_HINT_TEXT'),
			'analyticsSection' => 'ava_menu',
		];
	}

	private function getCounter(): string
	{
		if (!Loader::includeModule('intranet'))
		{
			return '0';
		}

		if (!$this->shouldShowGoToWebCounter())
		{
			return '0';
		}

		$userService = ServiceContainer::getInstance()->getUserService();

		$lastWebLoginTimestamp = $userService->getLastAuthFromWebTimestamp((int)$this->context->userId);
		if ($lastWebLoginTimestamp !== null)
		{
			self::setShouldShowGoToWebCounter(false);

			return '0';
		}

		$firstMobileLoginTimestamp = $userService->getFirstTimeAuthFromMobileAppTimestamp((int)$this->context->userId);
		if ($firstMobileLoginTimestamp === null)
		{
			self::setShouldShowGoToWebCounter(false);

			return '0';
		}

		return (time() - $firstMobileLoginTimestamp > 86400 ? '1' : '0');
	}

	private function shouldShowGoToWebCounter(): bool
	{
		return \CUserOptions::GetOption('mobile', 'avamenu_showGoToWebCounter', 'Y') === 'Y';
	}

	public static function setShouldShowGoToWebCounter(bool $value): void
	{
		\CUserOptions::SetOption('mobile', 'avamenu_showGoToWebCounter', ($value ? 'Y' : 'N'));
	}
}
