<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Traits\Singleton;

class QueryBuilderFactory
{
	use Singleton;

	public function make(
		array $permissionEntityTypes,
		UserPermissions $userPermissions,
		?QueryBuilderOptions $options = null
	): QueryBuilder
	{
		return new QueryBuilder($permissionEntityTypes, $userPermissions, $options);
	}
}
