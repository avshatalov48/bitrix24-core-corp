<?php

namespace Bitrix\Crm\Service\Router\Page\ItemDetails;

use Bitrix\Crm\Service\Router\Enum\Scope;
use CCrmOwnerType;

final class QuoteDetailsPage extends DynamicDetailsPage
{
	protected const ENTITY_TYPE_ID = CCrmOwnerType::Quote;
	protected const COMPONENT_NAME = 'bitrix:crm.quote.details';

	public static function scopes(): array
	{
		return [
			Scope::Crm,
		];
	}
}
