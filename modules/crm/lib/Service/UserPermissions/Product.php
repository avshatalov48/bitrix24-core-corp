<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Type;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->product()
 */
final class Product
{
	public function __construct(
		private readonly Admin $admin,
		private readonly Type $entityType,
	)
	{
	}


	public function canAdd(): bool
	{
		return $this->admin->isCrmAdmin();
	}

	public function canUpdate(): bool
	{
		return $this->admin->isCrmAdmin();
	}

	public function canDelete(): bool
	{
		return  $this->admin->isCrmAdmin();
	}

	public function canRead(): bool
	{
		return $this->entityType->canReadSomeItemsInCrm();
	}
}
