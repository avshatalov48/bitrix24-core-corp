<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Localization\Loc;

class Messenger extends BaseRecent
{
	use MessengerComponentTitle;

	public function isAvailable(): bool
	{
		return true;
	}

	public function isPreload(): bool
	{
		return true;
	}

	public function getId(): string
	{
		return 'chats';
	}

	public function getComponentCode(): string
	{
		return 'im.messenger';
	}

	protected function getTabTitle(): ?string
	{
		return Loc::getMessage("TAB_NAME_IM_RECENT_SHORT");
	}

	protected function getComponentName(): string
	{
		return 'im:messenger';
	}

	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats',
			'COMPONENT_CODE' => $this->getComponentCode(),
			'MESSAGES' => [
				'COMPONENT_TITLE' => $this->getTitle(),
			],
		];
	}

	protected function getWidgetSettings(): array
	{
		return [
			'useSearch' => true,
			'preload' => $this->isPreload(),
			'titleParams' => [
				'useLargeTitleMode' => true,
				'text' => $this->getTitle(),
			],
		];
	}

	protected function isWidgetSupported(): bool
	{
		return true;
	}
}
