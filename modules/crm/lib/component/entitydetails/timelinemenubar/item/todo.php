<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
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
		$options = \CUserOptions::getOption('crm', 'todo', []);
		$isTourViewedInWeb = (bool)($options['isTimelineTourViewedInWeb'] ?? false);
		$isHideAllTours = (Option::get('crm.tour', 'HIDE_ALL_TOURS', 'N') === 'Y');

		return [
			'currentUser' => $this->getCurrentUser(),
			'pingSettings' => $this->getPingSettings(),
			'copilotSettings' => $this->getCopilotSettings(),
			'colorSettings' => $this->getColorSettings(),
			'calendarSettings' => $this->getCalendarSettings(),
			'actionMenuSettings' => $this->getActionMenuSettings(),
			'isTourViewed' => $isTourViewedInWeb || $isHideAllTours,
		];
	}

	final protected function getCurrentUser(): array
	{
		$currentUser = Container::getInstance()
			->getUserBroker()
			->getById(CurrentUser::get()->getId());

		return [
			'userId' => $currentUser['ID'] ?? 0,
			'title' => $currentUser['FORMATTED_NAME'] ?? '',
			'detailUrl' => $currentUser['SHOW_URL'] ?? '',
			'imageUrl' => $currentUser['PHOTO_URL'] ?? '',
		];
	}

	final protected function getPingSettings(): array
	{
		return (new TodoPingSettingsProvider(
			$this->getEntityTypeId(),
			$this->getEntityCategoryId() ?? 0
		))->fetchForJsComponent();
	}

	final protected function getCopilotSettings(): array
	{
		if (!AIManager::isEnabledInGlobalSettings(
			EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE
		))
		{
			return [];
		}

		return [
			'moduleId' => 'crm',
			'contextId' => 'crm_timeline_todo_editor_add_item_' . CurrentUser::get()->getId(),
			'category' => 'crm_activity',
			'autoHide' => true,
		];
	}

	final protected function getColorSettings(): array
	{
		return (new ColorSettingsProvider())->fetchForJsComponent();
	}

	final protected function getCalendarSettings(): array
	{
		return (new CalendarSettingsProvider())->fetchForJsComponent();
	}

	final protected function getActionMenuSettings(): array
	{
		$entityTypeId = $this->context->getEntityTypeId();
		$hiddenActionItems = [];
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && !$factory->isClientEnabled())
			{
				$hiddenActionItems[] = 'client';
			}
		}

		return [
			'hiddenActionItems' => $hiddenActionItems,
		];
	}
}
