<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;

class TasksTab extends BaseProfileTab
{
	/**
	 * @return TabType
	 */
	public function getType(): TabType
	{
		return TabType::TASKS;
	}

	/**
	 * @return TabContextType
	 */
	public function getContextType(): TabContextType
	{
		return TabContextType::COMPONENT;
	}

	/**
	 * @return string|null
	 */
	public function getComponentName(): ?string
	{
		return 'tasks:tasks.dashboard';
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$isToolAvailable = (
			!Loader::includeModule('intranet')
			|| ToolsManager::getInstance()->checkAvailabilityByToolId('tasks')
		);

		return $isToolAvailable
			&& Loader::includeModule('tasks')
			&& Loader::includeModule('tasksmobile');
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('PROFILE_TAB_TASKS_TITLE');
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return [
			'USER_ID' => $this->ownerId,
		];
	}
}
