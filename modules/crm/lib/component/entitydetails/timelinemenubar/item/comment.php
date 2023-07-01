<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

class Comment extends Item
{
	public function getId(): string
	{
		return 'comment';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_COMMENT');
	}

	public function isAvailable(): bool
	{
		return true;
	}
}
