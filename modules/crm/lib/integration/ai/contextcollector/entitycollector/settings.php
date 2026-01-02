<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector;

final class Settings
{
	private bool $isCollectCategories = true;
	private readonly UserFieldsSettings $userFieldsSettings;
	private readonly StageSettings $stageSettings;

	public function __construct()
	{
		$this->userFieldsSettings = new UserFieldsSettings();
		$this->stageSettings = new StageSettings();
	}

	/**
	 * @param callable(UserFieldsSettings $settings): void $configurator
	 * @return $this
	 */
	public function configureUserFieldsSettings(callable $configurator): self
	{
		$configurator($this->userFieldsSettings);

		return $this;
	}

	public function userFields(): UserFieldsSettings
	{
		return $this->userFieldsSettings;
	}

	public function isCollectCategories(): bool
	{
		return $this->isCollectCategories;
	}

	public function setIsCollectCategories(bool $isCollectCategories): self
	{
		$this->isCollectCategories = $isCollectCategories;

		return $this;
	}

	/**
	 * @param callable(StageSettings $settings): void $configurator
	 * @return $this
	 */
	public function configureStageSettings(callable $configurator): self
	{
		$configurator($this->stageSettings);

		return $this;
	}

	public function stages(): StageSettings
	{
		return $this->stageSettings;
	}
}
