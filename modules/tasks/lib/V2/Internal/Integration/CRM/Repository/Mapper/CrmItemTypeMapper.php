<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\Type;
use CCrmOwnerType;

class CrmItemTypeMapper
{
	public function mapToEnum(int $type): Type
	{
		return match ($type)
		{
			CCrmOwnerType::Deal => Type::Deal,
			CCrmOwnerType::Lead => Type::Lead,
			CCrmOwnerType::Company => Type::Company,
			CCrmOwnerType::Contact => Type::Contact,
			default => Type::Unknown,
		};
	}
}