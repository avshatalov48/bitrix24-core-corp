<?php

namespace Bitrix\HumanResources\Service\Access;

use Bitrix\HumanResources\Access\Role\RoleUtil;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\DB\SqlQueryException;

class RoleRelationService implements \Bitrix\HumanResources\Contract\Service\Access\RoleRelationService
{
	/**
	 * @throws RoleRelationSaveException
	 */
	public function saveRoleRelation(array $settings): void
	{
		foreach ($settings as $setting)
		{
			$roleId = $setting['id'];
			if ($roleId === false)
			{
				continue;
			}

			(new RoleUtil($roleId))->updateRoleRelations($setting['accessCodes'] ?? []);
		}

		Container::getCacheManager()->clean(NodeRepository::NODE_ENTITY_RESTRICTION_CACHE);
	}

	/**
	 * @throws SqlQueryException
	 */
	public function deleteRelationsByRoleId(int $roleId): void
	{
		if (!Container::getAccessRoleRelationRepository()->deleteRelationsByRoleId($roleId))
		{
			throw new SqlQueryException();
		}
	}

	public function getRelationList(array $parameters = []): array
	{
		return Container::getAccessRoleRelationRepository()->getRelationList($parameters);
	}
}