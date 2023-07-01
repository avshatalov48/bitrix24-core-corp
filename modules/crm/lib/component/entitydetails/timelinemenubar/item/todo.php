<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\CurrentUser;

class ToDo extends Item
{
	public function getId(): string
	{
		return 'todo';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_ADD_TODO');
	}

	public function isAvailable(): bool
	{
		return !$this->isCatalogEntityType() && \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled();
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
			'enableTodoCalendarSync' => \Bitrix\Crm\Settings\Crm::isTimelineToDoCalendarSyncEnabled(),
		];
	}
}
