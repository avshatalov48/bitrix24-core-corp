<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Timeline\Entity\NoteTable;

abstract class LogMessage extends Configurable
{
	public function getIconCode(): ?string
	{
		return 'info';
	}

	public function isLogMessage(): bool
	{
		return true;
	}

	public function getMenuItems(): ?array
	{
		return null;
	}

	protected function getPinButton(): ?ChangeStreamButton
	{
		return null;
	}

	protected function getUnpinButton(): ?ChangeStreamButton
	{
		return null;
	}
}
