<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;

/**
 * @method self setId(int $id)
 */
final class DeleteCommand extends AbstractCommand
{
	#[Primary]
	#[PositiveNumber]
	public int $id;
}