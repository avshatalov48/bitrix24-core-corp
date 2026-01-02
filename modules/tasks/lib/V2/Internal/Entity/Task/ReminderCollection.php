<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\Recipient;

/**
 * @method Reminder[] getIterator()
 * @method array getTaskIdList()
 * @method array getUserIdList()
 * @method Recipient[] getRecipientList()
 * @method null|Reminder getFirstEntity()
 */
class ReminderCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Reminder::class;
	}
}