<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;

abstract class Tool
{
	abstract public function getId(): string;

	abstract public function getName(): string;

	abstract public function isAvailable(): bool;

	abstract public function getSubgroupsIds(): array;

	abstract public function getSubgroups(): array;

	abstract public function getMenuItemId(): ?string;

	public function getOptionCode(): string
	{
		return 'tool_' . $this->getId() . '_main';
	}

	public function getSubgroupSettingsPath(): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getAdditionalMenuItemIds(): array
	{
		return [];
	}

	public function getOptionName(): string
	{
		return 'intranet';
	}

	public function getSubgroupCode(string $id): string
	{
		return 'tool_subgroup_' . $this->getId() . '_' . $id;
	}

	public function getSettingsPath(): ?string
	{
		return null;
	}

	public function getLeftMenuPath(): ?string
	{
		return $this->getSettingsPath();
	}

	public function getSettingsTitle(): ?string
	{
		return null;
	}

	public function getInfoHelperSlider(): ?string
	{
		return null;
	}

	public function isEnabled(): bool
	{
		return $this->isAvailable() && Option::get('intranet', $this->getOptionCode(), 'Y') === 'Y';
	}

	public function isDefault(): bool
	{
		return false;
	}

	public function isEnabledSubgroups(): bool
	{
		$subgroupsIds = $this->getSubgroupsIds();
		if (empty($subgroupsIds))
		{
			return true;
		}

		foreach ($subgroupsIds as $subgroupsId => $menuItemId)
		{
			if ($this->isEnabledSubgroupById($subgroupsId))
			{
				return true;
			}
		}

		return false;
	}

	public function enable(): void
	{
		$this->setOptionEnabledState($this->getOptionCode());
		ServiceLocator::getInstance()->get('intranet.customSection.manager')->clearLeftMenuCache();
	}

	public function disable(): void
	{
		if ($this->isDefault())
		{
			return;
		}

		$this->setOptionDisabledState($this->getOptionCode());
		$this->disableAllSubgroups();
		ServiceLocator::getInstance()->get('intranet.customSection.manager')->clearLeftMenuCache();
	}

	public function enableSubgroup(string $code): void
	{
		$this->setOptionEnabledState($code);
	}

	public function disableSubgroup(string $code): void
	{
		$this->setOptionDisabledState($code);
	}

	public function disableAllSubgroups(): void
	{
		$subgroups = $this->getSubgroups();

		foreach ($subgroups as $subgroup)
		{
			$this->setOptionDisabledState($subgroup['code']);
		}
	}

	public function enableAllSubgroups(): void
	{
		$subgroups = $this->getSubgroups();

		foreach ($subgroups as $subgroup)
		{
			$this->setOptionEnabledState($subgroup['code']);
		}
	}

	public function isEnabledSubgroupByOptionCode(string $code): bool
	{
		return $this->isEnabled() && Option::get('intranet', $code, 'Y') === 'Y';
	}

	public function isEnabledSubgroupById(string $id): bool
	{
		$code = $this->getSubgroupCode($id);

		return $this->isEnabled() && Option::get('intranet', $code, 'Y') === 'Y';
	}

	private function setOptionDisabledState(string $optionCode): void
	{
		Option::set($this->getOptionName(), $optionCode, 'N');
		ToolsManager::getInstance()->clearCache();
	}

	private function setOptionEnabledState(string $optionCode): void
	{
		Option::set($this->getOptionName(), $optionCode, 'Y');
		ToolsManager::getInstance()->clearCache();
	}
}