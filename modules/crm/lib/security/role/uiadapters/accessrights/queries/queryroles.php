<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries;

use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsEntitySerializer;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\UserGroupBuilder;

class QueryRoles
{
	public function __construct(
		private readonly RoleSelectionManager $manager,
	)
	{
	}

	public function execute(): AccessRightsDTO
	{
		$entities = $this->manager->buildModels();
		$accessRights = (new AccessRightsEntitySerializer())->serialize($entities);

		$userGroupsBuilder = (new UserGroupBuilder());
		$userGroupsBuilder
			->isFilterByAccessRightsCodes($accessRights)
			->isExcludeRolesWithoutRights()
		;

		if ($this->manager->needShowRoleWithoutRights())
		{
			$userGroupsBuilder->includeRolesWithoutRightsForGroupCode((string)$this->manager->getGroupCode());
		}

		$userGroups = $userGroupsBuilder->build();

		return new AccessRightsDTO($accessRights, $userGroups);
	}
}
