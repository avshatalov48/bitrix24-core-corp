<?php

namespace Bitrix\Tasks\Flow\Efficiency\Command;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Range;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method self setFlowId(int $flowId)
 * @method self setOldEfficiency(int $oldEfficiency)
 * @method self setNewEfficiency(int $newEfficiency)
 */
class EfficiencyCommand extends AbstractCommand
{
	#[Required]
	#[PositiveNumber]
	public int $flowId;

	#[Required]
	#[Range(0, 100)]
	public int $oldEfficiency;

	#[Required]
	#[Range(0, 100)]
	public int $newEfficiency;
}