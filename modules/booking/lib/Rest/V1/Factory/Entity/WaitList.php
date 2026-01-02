<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;

class WaitList extends EntityFactory
{
	public function createFromRestFields(
		array $fields,
		?Entity\WaitListItem\WaitListItem $waitListItem = null,
	): Entity\WaitListItem\WaitListItem
	{
		if (!$waitListItem)
		{
			$waitListItem = new Entity\WaitListItem\WaitListItem();
		}

		if (isset($fields['NOTE']))
		{
			$waitListItem->setNote((string)$fields['NOTE']);
		}

		return $waitListItem;
	}
}
