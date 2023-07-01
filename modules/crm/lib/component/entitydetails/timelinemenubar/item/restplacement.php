<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Main\UI\Extension;

class RestPlacement extends Item
{
	private string $appId = '';
	private string $appName = '';
	private string $placementId = '';
	private string $placementTitle = '';

	public function getId(): string
	{
		return 'activity_rest_' . $this->getAppId() . '_' . $this->getPlacementId();
	}

	public function getName(): string
	{
		return $this->getPlacementTitle() !== '' ? $this->getPlacementTitle() : $this->getAppName();
	}

	public function isAvailable(): bool
	{
		return \Bitrix\Main\ModuleManager::isModuleInstalled('rest');
	}


	public function loadAssets(): void
	{
		Extension::load('applayout');
	}

	public function getAppId(): string
	{
		return $this->appId;
	}

	public function setAppId(string $appId): self
	{
		$this->appId = $appId;

		return $this;
	}

	public function getAppName(): string
	{
		return $this->appName;
	}

	public function setAppName(string $appName): self
	{
		$this->appName = $appName;

		return $this;
	}

	public function getPlacementId(): string
	{
		return $this->placementId;
	}

	public function setPlacementId(string $placementId): self
	{
		$this->placementId = $placementId;

		return $this;
	}

	public function getPlacementTitle(): string
	{
		return $this->placementTitle;
	}

	public function setPlacementTitle(string $placementTitle): self
	{
		$this->placementTitle = $placementTitle;

		return $this;
	}

	public function prepareSettings():array
	{
		return [
			'placement' => AppPlacement::getDetailActivityPlacementCode($this->getEntityTypeId()),
			'appId' => $this->getAppId(),
			'placementId' => $this->getPlacementId(),
		];
	}
}
