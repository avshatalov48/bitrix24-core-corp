<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Control\Command;

use Bitrix\Tasks\Flow\AbstractCommand;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataStatus;
use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method self setFlowId(int $flowId)
 * @method self setData(array $data)
 * @method self setStatus(CollectedDataStatus $status):
 */
class ReplaceCollectedDataCommand extends AbstractCommand
{
	#[Primary]
	#[Required]
	#[PositiveNumber]
	public int $flowId;

	public array $data = [];

	public ?CollectedDataStatus $status = null;
}
