<?php

namespace Bitrix\Tasks\Control\Log\Command;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Control\Log\Change;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Attribute\User;

/**
 * @method void validateAdd(string ...$skippedFields)
 * @method self setCreatedDate($createdDate)
 * @method bool hasValidCreatedDate()
 * @method self setUserId($userId)
 * @method bool hasValidUserId()
 * @method self setTaskId($taskId)
 * @method bool hasValidTaskId()
 * @method self setField($field)
 * @method bool hasValidField()
 * @method self setChange($change)
 * @method bool hasValidChange()
 */
class AddCommand extends AbstractCommand
{
	#[Required]
	public DateTime $createdDate;

	#[Required]
	#[Min(0)]
	#[User]
	public int $userId;

	#[Required]
	#[PositiveNumber]
	public int $taskId;

	#[Required]
	public string $field;

	#[Nullable]
	public ?Change $change = null;
}
