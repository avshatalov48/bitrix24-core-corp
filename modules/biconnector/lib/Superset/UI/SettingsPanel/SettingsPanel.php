<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel;

use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\EntityEditorController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Section\EntityEditorSection;

final class SettingsPanel
{
	private ?string $entityId = null;

	/** @var EntityEditorSection[] */
	private array $sections = [];

	/** @var EntityEditorController[] */
	private array $controllers = [];

	/** @var array */
	private array $ajaxData = [];

	public function __construct(private string $guid)
	{}

	private function getGuid(): string
	{
		return $this->guid;
	}

	public function setEntityId(string $entityId): self
	{
		$this->entityId = $entityId;

		return $this;
	}

	private function getEntityId(): ?string
	{
		return $this->entityId;
	}

	public function addSection(EntityEditorSection ...$sections): self
	{
		$this->sections = [...$this->sections, ...$sections];

		return $this;
	}

	public function addController(EntityEditorController ...$controller): self
	{
		$this->controllers = [...$this->controllers, ...$controller];

		return $this;
	}

	private function getSectionList(): array
	{
		return $this->sections;
	}

	private function getControllers(): array
	{
		return $this->controllers;
	}

	private function getAjaxData(): array
	{
		return $this->ajaxData;
	}

	public function setAjaxData(array $ajaxData): self
	{
		$this->ajaxData = $ajaxData;

		return $this;
	}

	public function show(): void
	{
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:biconnector.apachesuperset.settings.panel',
			'',
			$this->getSettingsPanelParams(),
		);
	}

	private function getSettingsPanelParams(): array
	{
		return [
			'GUID' => $this->getGuid(),
			'INITIAL_MODE' => 'edit',
			'ENTITY_ID' => $this->getEntityId(),
			'ENTITY_TYPE_NAME' => 'dashboardSettings',
			'SECTION_LIST' => $this->getSectionList(),
			'ENTITY_CONTROLLERS' => $this->getControllers(),

			'COMPONENT_AJAX_DATA' => $this->getAjaxData(),
		];
	}
}