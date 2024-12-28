<?php

namespace Bitrix\Intranet\User\Filter;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Filter\UserSettings;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\UserToGroupTable;

class IntranetUserSettings extends UserSettings
{
	public const ADMIN_FIELD = 'ADMIN';
	public const FIRED_FIELD = 'FIRED';
	public const INVITED_FIELD = 'INVITED';
	public const WAIT_CONFIRMATION_FIELD = 'WAIT_CONFIRMATION';
	public const INTEGRATOR_FIELD = 'INTEGRATOR';
	public const VISITOR_FIELD = 'VISITOR';

	protected array $filterAvailability;

	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->initFilterAvailability();
	}

	public function getFilterAvailability(): array
	{
		return $this->filterAvailability;
	}

	public function isFilterAvailable(string $filterField): bool
	{
		return $this->getFilterAvailability()[$filterField] ?? true;
	}

	public function isCurrentUserAdmin(): bool
	{
		return CurrentUser::get()->isAdmin();
	}

	public function getCurrentUserId(): int
	{
		return CurrentUser::get()->getId();
	}

	private function initFilterAvailability(): void
	{
		$isExtranetSite = Loader::includeModule('extranet') && \CExtranet::isExtranetSite();
		$canEditAllUsers = CurrentUser::get()->canDoOperation('edit_all_users');

		$this->filterAvailability[self::ADMIN_FIELD] =
			$canEditAllUsers
			&& (
				!ModuleManager::isModuleInstalled('extranet')
				|| Option::get('extranet', 'extranet_site', '') === ''
				|| !$isExtranetSite
			);

		$this->filterAvailability[self::FIRED_FIELD] =
			(
				$canEditAllUsers || Option::get('bitrix24', 'show_fired_employees', 'Y') === 'Y'
			)
			&& !$isExtranetSite;

		$this->filterAvailability[self::INVITED_FIELD] =
			!ModuleManager::isModuleInstalled('extranet')
			|| Option::get('extranet', 'extranet_site') == ''
			|| !$isExtranetSite;

		$this->filterAvailability[self::WAIT_CONFIRMATION_FIELD] =
			ModuleManager::isModuleInstalled('bitrix24')
			&& CurrentUser::get()->IsAdmin();

		$this->filterAvailability[self::INTEGRATOR_FIELD] =
			$canEditAllUsers
			&& ModuleManager::isModuleInstalled('bitrix24')
			&& (
				!ModuleManager::isModuleInstalled('extranet')
				|| (
					Option::get("extranet", "extranet_site") <> ''
					&& !$isExtranetSite
				)
			);

		$this->filterAvailability[self::VISITOR_FIELD] = $canEditAllUsers;
	}
}