<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

class ToDo extends Item
{
	public function getId(): string
	{
		return 'todo';
	}

	public function getName(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ADD_TODO');
	}

	public function isAvailable(): bool
	{
		return !$this->isCatalogEntityType();
	}

	public function prepareSettings(): array
	{
		$currentUser = Container::getInstance()
			->getUserBroker()
			->getById(CurrentUser::get()->getId());

		return [
			'currentUser' => [
				'userId' => $currentUser['ID'] ?? 0,
				'title' => $currentUser['FORMATTED_NAME'] ?? '',
				'detailUrl' => $currentUser['SHOW_URL'] ?? '',
				'imageUrl' => $currentUser['PHOTO_URL'] ?? '',
			],
			'pingSettings' => (new TodoPingSettingsProvider(
				$this->getEntityTypeId(),
				$this->getEntityCategoryId() ?? 0
			))->fetchForJsComponent(),
			'copilotSettings' => AIManager::isEnabledInGlobalSettings(EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE)
				? [
					'moduleId' => 'crm',
					'contextId' => 'crm_timeline_todo_editor_add_item_' . CurrentUser::get()->getId(),
					'category' => 'crm_activity',
					'autoHide' => true,
				]
				: [],
			'enableTodoCalendarSync' => \Bitrix\Crm\Settings\Crm::isTimelineToDoCalendarSyncEnabled(),
		];
	}
}
