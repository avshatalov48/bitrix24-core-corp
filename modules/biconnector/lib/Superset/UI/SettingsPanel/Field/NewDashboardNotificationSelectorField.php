<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Superset\SystemDashboardManager;

class NewDashboardNotificationSelectorField extends EntityEditorField
{
	public const FIELD_NAME = 'NOTIFICATION_SELECTOR';
	public const FIELD_ENTITY_EDITOR_TYPE = 'userNotificationSelector';

	public function getFieldInitialData(): array
	{
		return [
			'NOTIFICATION_SELECTOR' => SystemDashboardManager::getNewDashboardNotificationUserIds(),
		];
	}

	public function getName(): string
	{
		return static::FIELD_NAME;
	}

	public function getType(): string
	{
		return static::FIELD_ENTITY_EDITOR_TYPE;
	}

	protected function getFieldInfoData(): array
	{
		return [];
	}
}
