<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class NoActiveDestinationsException extends EntityConversionException
{
	public function __construct(int $srcEntityTypeId)
	{
		parent::__construct(
			$srcEntityTypeId,
			\CCrmOwnerType::Undefined,
			self::TARG_SRC,
			self::NO_ACTIVE_DESTINATIONS,
		);
	}
}
