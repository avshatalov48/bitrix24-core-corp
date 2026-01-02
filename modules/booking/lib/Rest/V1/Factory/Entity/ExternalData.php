<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;

class ExternalData extends EntityFactory
{
	public function createCollection(): ExternalDataCollection
	{
		return new ExternalDataCollection();
	}

	public function createFromRestFields(array $fields): ExternalDataItem
	{
		$externalData = new ExternalDataItem();

		$externalData->setModuleId((string)$fields['MODULE_ID']);
		$externalData->setEntityTypeId((string)$fields['ENTITY_TYPE_ID']);
		$externalData->setValue((string)$fields['VALUE']);

		return $externalData;
	}
}
