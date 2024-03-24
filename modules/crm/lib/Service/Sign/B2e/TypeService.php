<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

/**
 * Service for working with b2e documents.
 */
final class TypeService
{
	public function isCreated(): bool
	{
		$result = TypeTable::getByEntityTypeId(CCrmOwnerType::SmartB2eDocument)->fetchObject();

		return is_object($result);
	}

	public function getDefaultCategoryId(): int
	{
		$result = Container::getInstance()
			->getFactory(CCrmOwnerType::SmartB2eDocument)
			->getDefaultCategory()
			?->getId()
		;

		return (int) $result;
	}
}
