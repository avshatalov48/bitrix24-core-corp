<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;
use Bitrix\Booking\Entity\Booking\ExternalDataItem;
use Bitrix\Booking\Internals\Model\EO_BookingExternalData;

class ExternalDataItemMapper
{
	public function convertFromOrm(EO_BookingExternalData $ormBookingExternalDataItem): ExternalDataItem
	{
		return (new ExternalDataItem())
			->setModuleId($ormBookingExternalDataItem->getModuleId())
			->setEntityTypeId($ormBookingExternalDataItem->getEntityTypeId())
			->setValue($ormBookingExternalDataItem->getValue())
			->setId($ormBookingExternalDataItem->getId());
	}
}
