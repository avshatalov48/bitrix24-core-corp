<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class DestinationItemNotFoundException extends EntityConversionException
{
	public function __construct(int $dstEntityTypeID, int $destinationID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($dstEntityTypeID);

		parent::__construct(
			\CCrmOwnerType::Undefined,
			$dstEntityTypeID,
			self::TARG_DST,
			self::NOT_FOUND,
			"Conversion destination item of type {$entityTypeName} with ID = {$destinationID} not found",
		);
	}
}
