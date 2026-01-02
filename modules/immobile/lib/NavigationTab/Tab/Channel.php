<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\Im\V2\Entity\User\UserType;
use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImMobile\User;

class Channel extends BaseRecent
{
	use MessengerComponentTitle;

	public function isAvailable(): bool
	{
		$userType = User::getCurrent()?->getType();

		return $userType === UserType::USER;
	}

	public function isPreload(): bool
	{
		return false;
	}

	public function getId(): string
	{
		return 'channel';
	}

	public function getComponentCode(): string
	{
		return 'im.channel.messenger';
	}

	protected function getTabTitle(): ?string
	{
		return Loc::getMessage('IMMOBILE_NAVIGATION_TAB_CHANNEL_TAB_TITLE') ?? 'Channel'; //TODO delete fallback after translate
	}

	protected function getComponentName(): string
	{
		return 'im:channel-messenger';
	}

	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats.channel',
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
