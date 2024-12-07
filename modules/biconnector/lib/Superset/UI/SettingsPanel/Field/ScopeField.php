<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Superset\Scope\ScopeService;

final class ScopeField extends EntityEditorField
{
	public const FIELD_NAME = 'SCOPE';
	public const FIELD_ENTITY_EDITOR_TYPE = 'scopeSelector';
	private Dashboard $dashboard;

	public function __construct(string $id, Dashboard $dashboard)
	{
		parent::__construct($id);

		$this->dashboard = $dashboard;
	}

	public function getFieldInitialData(): array
	{
		$scope = ScopeService::getInstance()->getDashboardScopes($this->dashboard->getId());

		return [
			'SCOPE' => $scope,
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
