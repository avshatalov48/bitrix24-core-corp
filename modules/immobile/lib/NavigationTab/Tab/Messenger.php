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
	
	public function getId(): string
	{
		return 'chats';
	}
	
	protected function getTabTitle(): ?string
	{
		return Loc::getMessage("TAB_NAME_IM_RECENT_SHORT");
	}
	
	protected function getComponentCode(): string
	{
		return 'im.messenger';
	}
	
	protected function getComponentName(): string
	{
		return 'im:messenger';
	}
	
	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats',
			'COMPONENT_CODE' => 'im.messenger',
			'MESSAGES' => [
				'COMPONENT_TITLE' => $this->getTitle(),
			],
		];
	}
	
	protected function getWidgetSettings(): array
	{
		return [
			'useSearch' => true,
			'preload' => true,
			'titleParams' => [
				'useLargeTitleMode' => true,
				'text' => $this->getTitle(),
			],
		];
	}
}