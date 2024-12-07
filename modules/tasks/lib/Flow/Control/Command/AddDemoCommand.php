<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Attribute\Project;

/**
 * @method self setGroupId(int $groupId)
 */
final class AddDemoCommand extends AddCommand
{
	#[Required]
	#[Min(0)]
	#[Project]
	public int $groupId;
}
