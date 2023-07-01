<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Binding;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class Link extends Base
{
	public function getType(): string
	{
		return 'Link';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LINK_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::LINK;
	}
}
