<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries;

use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsEntitySerializer;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\UserGroupBuilder;
use Bitrix\Crm\Traits\Singleton;

class QueryRoles
{
	use Singleton;

	public function execute(): AccessRightsDTO
	{
		$eb = RoleManagementModelBuilder::getInstance();
		$entities = $eb->buildModels();

		$accessRights = (new AccessRightsEntitySerializer())->serialize($entities);

		$userGroups = UserGroupBuilder::getInstance()->build();

		return new AccessRightsDTO($accessRights, $userGroups);
	}
}