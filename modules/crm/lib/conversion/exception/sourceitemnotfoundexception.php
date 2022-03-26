<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class SourceItemNotFoundException extends EntityConversionException
{
	public function __construct(int $srcEntityTypeID, int $sourceID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($srcEntityTypeID);

		parent::__construct(
			$srcEntityTypeID,
			\CCrmOwnerType::Undefined,
			self::TARG_SRC,
			self::NOT_FOUND,
			"Conversion source item of type {$entityTypeName} with ID = {$sourceID} not found",
		);
	}
}
