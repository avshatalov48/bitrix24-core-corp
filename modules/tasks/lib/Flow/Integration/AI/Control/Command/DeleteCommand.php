<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Control\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method self setFlowId(int $flowId)
 */
class DeleteCommand extends AbstractCommand
{
	#[Primary]
	#[Required]
	#[PositiveNumber]
	public int $flowId;
}
