<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData\ItemType;

class CrmDealItemType extends BaseItemType
{
	public function getModuleId(): string
	{
		return 'crm';
	}

	public function getEntityTypeId(): string
	{
		return 'DEAL';
	}
}
