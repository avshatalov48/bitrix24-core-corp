<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\BIConnector\Superset\KeyManager;

class KeyInfoField extends EntityEditorField
{
	public const FIELD_NAME = 'KEY_INFO';
	public const FIELD_ENTITY_EDITOR_TYPE = 'keyInfo';

	public function __construct(string $id)
	{
		parent::__construct($id);
	}

	public function getFieldInitialData(): array
	{
		return [
			'KEY_INFO' => KeyManager::getAccessKey(),
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
		return [
			'key' => 'KEY_INFO',
		];
	}
}
