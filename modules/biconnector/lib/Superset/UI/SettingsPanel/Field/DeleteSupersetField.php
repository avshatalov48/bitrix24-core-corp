<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\BIConnector\Superset\KeyManager;

final class DeleteSupersetField extends EntityEditorField
{
	public const FIELD_NAME = 'DELETE_SUPERSET';
	public const FIELD_ENTITY_EDITOR_TYPE = 'deleteSuperset';

	public function getName(): string
	{
		return self::FIELD_NAME;
	}

	public function getType(): string
	{
		return self::FIELD_ENTITY_EDITOR_TYPE;
	}

	protected function getFieldInfoData(): array
	{
		return [];
	}

	public function getFieldInitialData(): array
	{
		return [];
	}
}