<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\ImMobile\Settings;
use Bitrix\Main\Localization\Loc;

class Collab extends BaseRecent
{
	use MessengerComponentTitle;
	
	public function isAvailable(): bool
	{
		return Settings::getImFeatures()?->collabAvailable ?? false;
	}
	
	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats.collab',
			'COMPONENT_CODE' => 'im.collab.messenger',
			'MESSAGES' => [
				'COMPONENT_TITLE' => $this->getTitle(),
			],
		];
	}

	protected function getWidgetSettings(): array
	{
		return [
			'useSearch' => true,
			'preload' => false,
			'titleParams' => [
				'useLargeTitleMode' => true,
				'text' => $this->getTitle(),
			],
		];
	}

	public function getId(): string
	{
		return 'collab';
	}

	protected function getTabTitle(): ?string
	{
		return Loc::getMessage('IMMOBILE_NAVIGATION_TAB_COLLAB_TAB_TITLE');
	}

	protected function getComponentCode(): string
	{
		return 'im.collab.messenger';
	}
	
	protected function getComponentName(): string
	{
		return 'im:collab-messenger';
	}
}
