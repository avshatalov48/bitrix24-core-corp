<?php

namespace Bitrix\Crm\Settings\Traits;

use Bitrix\Crm\Settings\BooleanSetting;

Trait UseNumberInTitlePlaceholder
{
	private $isUseNumberInTitlePlaceholder;

	private function initIsUseNumberInTitlePlaceholderSettings(int $entityTypeId, bool $defaultValue = false): void
	{
		$entityTypeName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

		$this->isUseNumberInTitlePlaceholder = new BooleanSetting($entityTypeName . '_use_number_in_title_placeholder', $defaultValue);
	}

	public function isUseNumberInTitlePlaceholder(): bool
	{
		return $this->isUseNumberInTitlePlaceholder->get();
	}

	public function setUserNumberInTitlePlaceholder(bool $isUserNumberInTitlePlaceholder): void
	{
		$this->isUseNumberInTitlePlaceholder->set($isUserNumberInTitlePlaceholder);
	}
}