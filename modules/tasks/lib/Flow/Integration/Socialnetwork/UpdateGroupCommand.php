<?php

namespace Bitrix\Tasks\Flow\Integration\Socialnetwork;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\ExpectedNumeric;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;

/**
 * @method self setId(int $id)
 * @method self setName(string $name)
 * @method self setOwnerId(int $ownerId)
 * @method self setMembers(array $members)
 */
class UpdateGroupCommand extends AbstractCommand
{
	#[Primary]
	public int $id;

	#[Nullable]
	public string $name;

	#[Nullable]
	#[PositiveNumber]
	public int $ownerId;

	#[Nullable]
	#[ExpectedNumeric]
	public array $members;
}
