<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;

final class OwnerField extends EntityEditorField
{
	public const FIELD_NAME = 'OWNER_ID';
	public const FIELD_ENTITY_EDITOR_TYPE = 'ownerSelector';

	private Dashboard $dashboard;

	public function __construct(string $id, Dashboard $dashboard)
	{
		parent::__construct($id);

		$this->dashboard = $dashboard;
	}

	public function getFieldInitialData(): array
	{
		$ownerId = $this->dashboard->getOrmObject()->getOwnerId();

		return [
			'OWNER_ID' => $ownerId,
		];
	}

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
}
