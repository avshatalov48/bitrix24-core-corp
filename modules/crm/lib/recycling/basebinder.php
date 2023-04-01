<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm\ItemIdentifier;

abstract class BaseBinder
{
	abstract public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID);
	abstract public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs);
	abstract public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs);

	final protected function getUnbindUpdateOptions(int $associatedEntityTypeID, array $associatedEntityIDs): array
	{
		if (!\CCrmOwnerType::IsDefined($associatedEntityTypeID))
		{
			return [];
		}

		$associatedEntities = [];
		foreach ($associatedEntityIDs as $associatedEntityID)
		{
			if ($associatedEntityID > 0)
			{
				$associatedEntities[] = new ItemIdentifier($associatedEntityTypeID, (int)$associatedEntityID);
			}
		}

		return [
			// don't register relation events while moving to/restoring from recycle bin
			'EXCLUDE_FROM_RELATION_REGISTRATION' => $associatedEntities,
		];
	}

	/**
	 * @deprecated
	 */
	protected function getUpdateOptions(int $associatedEntityTypeID, array $associatedEntityIDs): array
	{
		return $this->getUnbindUpdateOptions($associatedEntityTypeID, $associatedEntityIDs);
	}
}
