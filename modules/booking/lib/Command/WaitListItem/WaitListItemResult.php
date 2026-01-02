<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Entity\WaitListItem\WaitListItem;
use Bitrix\Main\Result;

class WaitListItemResult extends Result
{
	public function __construct(private readonly WaitListItem $waitListItem)
	{
		parent::__construct();
	}

	public function getWaitListItem(): WaitListItem
	{
		return $this->waitListItem;
	}
}
