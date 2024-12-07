<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\Im\V2\Chat\ChannelChat;
use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Localization\Loc;

class Channel extends BaseRecent
{
	use MessengerComponentTitle;
	
	public function isAvailable(): bool
	{
		return true;
	}
	
	public function getId(): string
	{
		return 'channel';
	}
	
	protected function getTabTitle(): ?string
	{
		return Loc::getMessage('IMMOBILE_NAVIGATION_TAB_CHANNEL_TAB_TITLE') ?? 'Channel'; //TODO delete fallback after translate
	}
	
	protected function getComponentCode(): string
	{
		return 'im.channel.messenger';
	}
	
	protected function getComponentName(): string
	{
		return 'im:channel-messenger';
	}
	
	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats.channel',
			'COMPONENT_CODE' => 'im.channel.messenger',
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