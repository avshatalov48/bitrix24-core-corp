<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class AutocreationDisabledException extends EntityConversionException
{
	public function __construct(int $dstEntityTypeId)
	{
		parent::__construct(
			\CCrmOwnerType::Undefined,
			$dstEntityTypeId,
			self::TARG_DST,
			self::AUTOCREATION_DISABLED,
		);
	}
}
