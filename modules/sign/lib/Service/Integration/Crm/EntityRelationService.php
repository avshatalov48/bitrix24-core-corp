<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Document;
use Bitrix\Crm\Service\Container;
use Bitrix\Sign\Type\Document\EntityType;

final class EntityRelationService
{
	public function addRelationToSmartB2eDocument(Document $document, int $entityId, int $entityTypeId): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module is not available'));
		}

		if ($document->entityType !== EntityType::SMART_B2E)
		{
			return $result->addError(new Error('Invalid entity type'));
		}

		if ($entityId < 1)
		{
			return $result->addError(new Error('Invalid entity id'));
		}

		if ($entityTypeId < 1)
		{
			return $result->addError(new Error('Invalid entity type id'));
		}

		$destinationEntityId = (int)$document->entityId;
		if ($destinationEntityId < 1)
		{
			return $result->addError(new Error('Invalid document entity id'));
		}

		$sourceItem = $this->getItem($entityId, $entityTypeId);
		if ($sourceItem === null)
		{
			return $result->addError(new Error('Source item not found'));
		}

		$destinationItem = $this->getItem($destinationEntityId, \CCrmOwnerType::SmartB2eDocument);
		if ($destinationItem === null)
		{
			return $result->addError(new Error('Destination item not found'));
		}

		return Container::getInstance()->getRelationManager()->bindItems(
			ItemIdentifier::createByItem($sourceItem),
			ItemIdentifier::createByItem($destinationItem),
		);
	}

	private function getItem(int $entityId, int $entityTypeId): ?Item
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($entityId < 1)
		{
			return null;
		}

		if ($entityTypeId < 1)
		{
			return null;
		}

		$sourceItemFactory = Container::getInstance()->getFactory($entityTypeId);

		return $sourceItemFactory?->getItem($entityId);
	}

	private function isAvailable():bool
	{
		return Storage::instance()->isAvailable();
	}
}
