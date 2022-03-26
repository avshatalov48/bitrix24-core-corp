<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

class SourceHasParentException extends EntityConversionException
{
	public function __construct(int $srcEntityTypeID, int $dstEntityTypeID)
	{
		$sourceTypeName = \CCrmOwnerType::ResolveName($srcEntityTypeID);
		$dstTypeName = \CCrmOwnerType::ResolveName($dstEntityTypeID);

		parent::__construct(
			$srcEntityTypeID,
			$dstEntityTypeID,
			self::TARG_SRC,
			self::INVALID_OPERATION,
			"You can not convert this {$sourceTypeName} item to a {$dstTypeName} item"
			. ' because the source item was converted from an item of the destination type',
		);
	}
}
