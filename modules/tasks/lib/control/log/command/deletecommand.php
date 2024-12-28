<?php

namespace Bitrix\Tasks\Control\Log\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method void validateDelete()
 * @method self setId(int $id)
 * @method bool hasValidId()
 */
class DeleteCommand extends AbstractCommand
{
	#[Required]
	#[PositiveNumber]
	#[Primary]
	public int $id;
}
