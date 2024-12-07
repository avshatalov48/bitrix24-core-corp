<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Main\UI\Extension;
use CUserOptions;

class RestPlacement extends Item
{
	private string $appId = '';
	private string $appName = '';
	private string $placementId = '';
	private string $placementTitle = '';
	private string $placementCode = '';
	private array $placementOptions = [];

	private const MODULE_ID = 'crm';
	private const USER_SEEN_OPTION = 'rest_placement_tour_viewed';

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

	public function getPlacementCode(): string
	{
		return $this->placementCode;
	}

	public function setPlacementCode(string $placementCode): self
	{
		$this->placementCode = $placementCode;

		return $this;
	}

	public function getPlacementOptions(): array
	{
		return $this->placementOptions;
	}

	public function setPlacementOptions(array $placementOptions): self
	{
		$this->placementOptions = $placementOptions;

		return $this;
	}

	private function isCanShowTour(): bool
	{
		if ($this->isHideAllTours())
		{
			return false;
		}

		$options = CUserOptions::GetOption(self::MODULE_ID, self::USER_SEEN_OPTION, []);
		$isTourViewed = (bool)($options[$this->getId()] ?? false);

		return !$isTourViewed;
	}

	public function prepareSettings():array
	{
		$placementOptions = $this->getPlacementOptions();
		return [
			'id' => $this->getId(),
			'placement' => $this->getPlacementCode(),
			'appId' => $this->getAppId(),
			'placementId' => $this->getPlacementId(),
			'useBuiltInInterface' => ($placementOptions['useBuiltInInterface'] ?? 'N') == 'Y',
			'newUserNotificationTitle' => ($placementOptions['newUserNotificationTitle'] ?? ''),
			'newUserNotificationText' => ($placementOptions['newUserNotificationText'] ?? ''),
			'isCanShowTour' => $this->isCanShowTour(),
		];
	}
}
