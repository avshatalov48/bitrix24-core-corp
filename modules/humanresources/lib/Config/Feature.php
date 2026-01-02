<?php

namespace Bitrix\HumanResources\Config;

use Bitrix\HumanResources\Internals\Trait\SingletonTrait;
use Bitrix\Main;
use Bitrix\Main\Application;

class Feature
{
	use SingletonTrait;

	private const MODULE_NAME = 'humanresources';
	private const FIRE_PERMISSION_AVAILABLE_OPTION_NAME = 'use_hr_fire_permission';
	private const STRUCT_INVITE_PERMISSION_OPTION_NAME = 'use_hr_invite_permission';
	private const CROSS_FUNCTIONAL_TEAMS_IS_AVAILABLE_OPTION_NAME = 'teams_available';
	private const COLLABS_IS_AVAILABLE_OPTION_NAME = 'collabs_available';
	private const DEPUTY_APPROVES_BP_IS_AVAILABLE_OPTION_NAME = 'deputy_approves_bp_available';
	private const DEPARTMENT_SETTINGS_IS_AVAILABLE_OPTION_NAME = 'department_settings_available';

	public function isHcmLinkAvailable(): bool
	{
		$regionCode = Application::getInstance()->getLicense()->getRegion();

		return in_array($regionCode, ['ru'], true);
	}

	public function isCrossFunctionalTeamsAvailable(): bool
	{
		return true; // enabled in 25.700.0
	}

	public function isCollabsAvailable(): bool
	{
		return true; // enabled in 25.1000.0
	}

	public function isDeputyApprovesBPAvailable(): bool
	{
		return $this->getOptionValue(self::DEPUTY_APPROVES_BP_IS_AVAILABLE_OPTION_NAME, 'N') === 'Y';
	}

	public function isDepartmentSettingsAvailable(): bool
	{
		return $this->getOptionValue(self::DEPARTMENT_SETTINGS_IS_AVAILABLE_OPTION_NAME, 'N') === 'Y';
	}

	public function setDeputyApprovesBPAvailable(bool $value): void
	{
		$stringValue = $value ? 'Y' : 'N';
		$this->setOptionValue(self::DEPUTY_APPROVES_BP_IS_AVAILABLE_OPTION_NAME, $stringValue);
	}

	public function isHRFirePermissionAvailable(): bool
	{
		return $this->getOptionValue(self::FIRE_PERMISSION_AVAILABLE_OPTION_NAME, 'N') === 'Y';
	}

	public function setHRFirePermissionAvailable(bool $value): void
	{
		$stringValue = $value ? 'Y' : 'N';
		$this->setOptionValue(self::FIRE_PERMISSION_AVAILABLE_OPTION_NAME, $stringValue);
	}

	public function isHRInvitePermissionAvailable(): bool
	{
		return Main\Config\Option::get(self::MODULE_NAME, self::STRUCT_INVITE_PERMISSION_OPTION_NAME, 'N') === 'Y';
	}

	public function setHRInvitePermissionAvailable(bool $value): void
	{
		$stringValue = $value ? 'Y' : 'N';
		$this->setOptionValue(self::STRUCT_INVITE_PERMISSION_OPTION_NAME, $stringValue);
	}

	private function getOptionValue(string $option, mixed $defaultValue)
	{
		return Main\Config\Option::get(self::MODULE_NAME, $option, $defaultValue);
	}

	private function setOptionValue(string $option, string $value)
	{
		Main\Config\Option::set(self::MODULE_NAME, $option, $value);
	}
}