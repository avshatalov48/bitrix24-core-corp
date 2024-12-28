<?php

namespace Bitrix\Intranet\User\Grid\Settings;

use Bitrix\Intranet\Component\UserList;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class UserSettings extends \Bitrix\Main\Grid\Settings
{
	private array $userFields;
	private string $extensionName;
	private string $extensionLoadName;
	private array $adminIdList = [];
	private ?array $integratorIdList = null;
	private array $viewFields;
	private ?array $filterFields = null;
	private ?bool $isInvitationAvailable = null;
	private int $userId;
	private ?UserCollection $userCollection = null;

	public function __construct(array $params)
	{
		parent::__construct($params);

		global $USER_FIELD_MANAGER;

		$this->userId = CurrentUser::get()->getId();
		$this->userFields = $USER_FIELD_MANAGER->getUserFields(\Bitrix\Main\UserTable::getUfId(), 0, LANGUAGE_ID, false);
		$this->initViewFields();

		$this->extensionName = $params['extensionName'] ?? 'Intranet.UserList';
		$this->extensionLoadName = $params['extensionLoadName'] ?? 'intranet.grid.user-grid';
	}

	public function getUserFields(): array
	{
		return $this->userFields;
	}

	public function getExtensionName(): string
	{
		return $this->extensionName;
	}

	public function getExtensionLoadName(): string
	{
		return $this->extensionLoadName;
	}

	public function isUserAdmin($userId): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::IsPortalAdmin($userId);
		}

		return in_array($userId, $this->getAdminIdList());
	}

	public function isCurrentUserAdmin(): bool
	{
		return $this->isUserAdmin($this->getCurrentUserId());
	}

	public function isUserIntegrator($userId): bool
	{
		return in_array($userId, $this->getIntegratorIdList());
	}

	public function getCurrentUserId(): int
	{
		return $this->userId;
	}

	public function getViewFields(): array
	{
		return $this->viewFields;
	}

	public function getFilterFields(): ?array
	{
		return $this->filterFields;
	}

	public function isCloud(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	public function setFilterFields(array $filterFields): void
	{
		$this->filterFields = $filterFields;
	}

	public function isInvitationAvailable(): bool
	{
		if (!isset($this->isInvitationAvailable))
		{
			$this->isInvitationAvailable = (
				CurrentUser::get()->canDoOperation('edit_all_users')
				|| (
					ModuleManager::isModuleInstalled('bitrix24')
					&& Option::get('bitrix24', 'allow_invite_users', 'N') === 'Y'
				)
			);
		}

		return $this->isInvitationAvailable;
	}

	/**
	 * @return UserCollection|null
	 */
	public function getUserCollection(): ?UserCollection
	{
		return $this->userCollection;
	}

	/**
	 * @param UserCollection $userCollection
	 */
	public function setUserCollection(UserCollection $userCollection): void
	{
		$this->userCollection = $userCollection;
	}

	private function initViewFields(): void
	{
		$result = [];
		$val = Option::get('intranet', 'user_list_user_property_available', false, SITE_ID);

		if (!empty($val))
		{
			$val = unserialize($val, ["allowed_classes" => false]);
			if (
				is_array($val)
				&& !empty($val)
			)
			{
				$result = $val;
			}
		}

		$this->viewFields = !empty($result) ? $result : UserList::getUserPropertyListDefault();
	}

	private function getAdminIdList(): array
	{
		if (empty($this->adminIdList))
		{
			$dbAdminList = \CAllGroup::GetGroupUserEx(1);

			while($admin = $dbAdminList->fetch())
			{
				$this->adminIdList[] = (int)$admin['USER_ID'];
			}
		}

		return $this->adminIdList;
	}

	private function getIntegratorIdList(): array
	{
		if (is_null($this->integratorIdList))
		{
			$this->integratorIdList =
				\Bitrix\Main\Loader::includeModule('bitrix24')
					? \Bitrix\Bitrix24\Integrator::getIntegratorsId()
					: [];
		}

		return $this->integratorIdList;
	}
}