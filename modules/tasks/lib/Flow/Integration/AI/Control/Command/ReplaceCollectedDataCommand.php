<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Control\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method self setFlowId(int $flowId)
 * @method self setData(array $data)
 */
class ReplaceCollectedDataCommand extends AbstractCommand
{
	#[Primary]
	#[Required]
	#[PositiveNumber]
	public int $flowId;

	#[Required]
	#[NotEmpty]
	public array $data;
}
