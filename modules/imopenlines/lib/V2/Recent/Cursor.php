<?php

namespace Bitrix\ImOpenLines\V2\Recent;

use Bitrix\ImOpenLines\V2\Status\StatusGroup;
use Bitrix\Main\Type\DateTime;

final class Cursor
{
	/*
	 * $sortPointer can be date of last operator's answer or sessionId
	 */
	private DateTime|int|null $sortPointer = null;
	private ?StatusGroup $statusGroup = null;

	public function __construct(DateTime|int|null $sortPointer = null, ?StatusGroup $statusGroup = null)
	{
		if (isset($sortPointer, $statusGroup))
		{
			$this->sortPointer = $sortPointer;
			$this->statusGroup = $statusGroup;
		}
	}

	public function getSortPointer(): DateTime|int|null
	{
		return $this->sortPointer;
	}

	public function getStatusGroup(): ?StatusGroup
	{
		return $this->statusGroup;
	}
}