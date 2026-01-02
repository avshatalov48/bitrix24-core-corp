<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\ImMobile\Settings;
use Bitrix\Main\Localization\Loc;

class Task extends BaseRecent
{
	use MessengerComponentTitle;

	public function isAvailable(): bool
	{
		if (!Settings::isMessengerV2Enabled())
		{
			return false;
		}

		if (!Settings::isTasksRecentListAvailable())
		{
			return false;
		}

		return true;
	}

	public function isPreload(): bool
	{
		return false;
	}

	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'chats.task',
			'COMPONENT_CODE' => 'im.task.messenger',
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

	public function getId(): string
	{
		return 'task';
	}

	protected function getTabTitle(): ?string
	{
		return Loc::getMessage('IMMOBILE_NAVIGATION_TAB_TASK_TAB_TITLE');
	}

	public function getComponentCode(): string
	{
		return '';
	}

	protected function getComponentName(): string
	{
		return '';
	}

	protected function isWidgetSupported(): bool
	{
		return true;
	}
}
