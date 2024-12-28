<?php

namespace Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager;

class CreateSettingsDto
{
	public ?string $criterion = null;
	public ?string $customSectionCode = null;

	public function getCriterion(): ?string
	{
		return $this->criterion;
	}

	public function setCriterion(?string $criterion): CreateSettingsDto
	{
		$this->criterion = $criterion;

		return $this;
	}

	public function getCustomSectionCode(): ?string
	{
		return $this->customSectionCode;
	}

	public function setCustomSectionCode(?string $customSectionCode): CreateSettingsDto
	{
		$this->customSectionCode = $customSectionCode;

		return $this;
	}
}
