<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Booking\Internals\Model\EO_ClientType;

class ClientTypeMapper
{
	public function convertFromOrm(EO_ClientType $ormClientType): ClientType
	{
		return
			(new ClientType())
				->setCode($ormClientType->getCode())
				->setModuleId($ormClientType->getModuleId())
			;
	}
}
