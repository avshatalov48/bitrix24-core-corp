<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Booking\Client;
use Bitrix\Booking\Entity\Booking\ClientType;
use Bitrix\Booking\Internals\Model\EO_BookingClient;

class ClientMapper
{
	public function convertFromOrm(EO_BookingClient $ormBookingClient): Client
	{
		//@todo needs to be removed after 47f02cffbcbc is released (approximately main 24.400.0 )
		if (!$ormBookingClient->isIsReturningFilled())
		{
			$ormBookingClient->fillIsReturning();
		}

		return (new Client())
			->setType(
				(new ClientType())
					->setModuleId($ormBookingClient->getClientType()?->getModuleId())
					->setCode($ormBookingClient->getClientType()?->getCode())
			)
			->setId($ormBookingClient->getClientId())
			->setIsReturning($ormBookingClient->getIsReturning())
		;
	}
}
