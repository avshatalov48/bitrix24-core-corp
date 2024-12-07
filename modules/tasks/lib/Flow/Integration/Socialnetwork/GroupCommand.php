<?php

namespace Bitrix\Tasks\Flow\Integration\Socialnetwork;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Internals\Attribute\ExpectedNumeric;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Required;

/**
 * @method self setName(string $name)
 * @method self setOwnerId(int $ownerId)
 * @method self setFlow(Flow $flow)
 * @method self setMembers(array $members)
 */
class GroupCommand extends AbstractCommand
{
	#[Nullable]
	public int $id;

	#[Required]
	public string $name;

	#[Required]
	#[PositiveNumber]
	public int $ownerId;

	#[Nullable]
	#[ExpectedNumeric]
	public array $members;
}