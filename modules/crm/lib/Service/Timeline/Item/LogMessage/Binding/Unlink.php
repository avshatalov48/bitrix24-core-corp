<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Binding;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class Unlink extends Base
{
	public function getType(): string
	{
		return 'Unlink';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_UNLINK_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::UNLINK;
	}
}
