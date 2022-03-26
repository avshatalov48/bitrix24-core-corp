<?php

namespace Bitrix\Crm\Conversion\Exception;

use Bitrix\Crm\Conversion\EntityConversionException;

final class DestinationHasWorkflowsException extends EntityConversionException
{
	public function __construct(int $dstEntityTypeID)
	{
		parent::__construct(
			\CCrmOwnerType::Undefined,
			$dstEntityTypeID,
			self::TARG_DST,
			self::HAS_WORKFLOWS,
		);
	}
}
