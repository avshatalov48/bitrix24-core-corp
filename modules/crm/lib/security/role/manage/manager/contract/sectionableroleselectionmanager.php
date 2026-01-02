<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager\Contract;

use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;

interface SectionableRoleSelectionManager extends RoleSelectionManager
{
	public function getTitle(): string;

	public function getControllerData(): array;
}
