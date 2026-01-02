<?php

namespace Bitrix\Crm\Entity;

use CCrmOwnerType;

final class EntityEditorOptionMap
{
	private const MAP = [
		CCrmOwnerType::Lead => 'lead_details',
		CCrmOwnerType::Deal => 'deal_details',
		CCrmOwnerType::Contact => 'contact_details',
		CCrmOwnerType::Company => 'company_details',
		CCrmOwnerType::Quote => 'QUOTE_details',
		CCrmOwnerType::StoreDocument => 'store_document_details',
		CCrmOwnerType::ShipmentDocument => 'realization_document_delivery_details',
	];

	public function option(int $entityTypeId): ?string
	{
		return self::MAP[$entityTypeId] ?? null;
	}

	public function entityTypeId(string $option): ?int
	{
		return array_flip(self::MAP)[$option] ?? null;
	}
}
