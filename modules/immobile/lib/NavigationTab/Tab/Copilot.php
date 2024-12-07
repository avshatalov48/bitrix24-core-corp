<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\Im\V2\Chat\CopilotChat;
use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Config\Option;

class Copilot extends BaseRecent
{
	use MessengerComponentTitle;
	
	public function isAvailable(): bool
	{
		return CopilotChat::isActive();
	}
	
	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats.copilot',
			'COMPONENT_CODE' => 'im.copilot.messenger',
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
	
	public function getId(): string
	{
		return 'copilot';
	}
	
	protected function getTabTitle(): ?string
	{
		return 'CoPilot';
	}
	
	protected function getComponentCode(): string
	{
		return 'im.copilot.messenger';
	}
	
	protected function getComponentName(): string
	{
		return 'im:copilot-messenger';
	}
}
