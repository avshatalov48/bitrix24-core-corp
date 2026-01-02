<?php

namespace Bitrix\Crm\Service\Router\Page\ItemDetails;

use Bitrix\Crm\Service\Router\Enum\Scope;

final class SmartInvoiceDetailsPage extends DynamicDetailsPage
{
	protected const ENTITY_TYPE_ID = \CCrmOwnerType::SmartInvoice;
	protected const COMPONENT_NAME = 'bitrix:crm.invoice.details';

	public static function scopes(): array
	{
		return [
			Scope::Crm,
		];
	}
}
