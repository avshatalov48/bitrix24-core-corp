<?php

namespace Bitrix\Crm\Settings\Traits;

use Bitrix\Crm\Settings\BooleanSetting;

trait EnableFactory
{
	/** @var BooleanSetting */
	private $isFactoryEnabled;

	private function initIsFactoryEnabledSetting(int $entityTypeId, bool $defaultValue = true): void
	{
		$entityTypeName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

		$this->isFactoryEnabled = new BooleanSetting("{$entityTypeName}_enable_factory", $defaultValue);
	}

	/**
	 * Return true if new interface and api through Service\Factory is used to process this entity type.
	 *
	 * @return bool
	 */
	public function isFactoryEnabled(): bool
	{
		return $this->isFactoryEnabled->get();
	}

	/**
	 * Set state of isFactoryEnabled setting.
	 *
	 * @param bool $isEnabled
	 */
	public function setFactoryEnabled(bool $isEnabled): void
	{
		$this->isFactoryEnabled->set($isEnabled);
	}
}
