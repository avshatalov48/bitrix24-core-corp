<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class CreationFailedException extends EntityConversionException
{
	public function __construct(int $dstEntityTypeID, string $extendedMessage)
	{
		parent::__construct(
			\CCrmOwnerType::Undefined,
			$dstEntityTypeID,
			EntityConversionException::TARG_DST,
			EntityConversionException::CREATE_FAILED,
			$extendedMessage,
		);
	}
}
