<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Traits\Singleton;

final class PermissionChecker
{
	use Singleton;

	public function userHasPermissionToEntity(ListParams\Params $listParams, UserPermissions $userPermissions): bool
	{
		$isAdmin = $userPermissions->isAdmin();
		if ($isAdmin)
		{
			return true;
		}

		$bindingsFilter = $listParams->getFilter()->getBindingsFilter();

		foreach ($bindingsFilter as $binding)
		{
			$context = new Context(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				Context::REST,
				$userPermissions->getUserId(),
			);

			if (!$context->canReadEntity())
			{
				return false;
			}

		}

		return true;
	}
}
