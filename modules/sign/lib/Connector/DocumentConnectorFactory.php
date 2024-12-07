<?php

namespace Bitrix\Sign\Connector;

use Bitrix\Sign\Connector\Crm\SmartB2eDocument;
use Bitrix\Sign\Connector\Crm\SmartDocument;
use Bitrix\Sign\Item;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Type;

class DocumentConnectorFactory
{
	public function create(Item\Document $document): ?Contract\Connector
	{
		return match ($document->entityType)
		{
			Type\Document\EntityType::SMART => new SmartDocument($document->entityId),
			Type\Document\EntityType::SMART_B2E => new SmartB2eDocument($document->entityId),
			default => null,
		};
	}
}