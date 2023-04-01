<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class MoneyPill extends Money
{
	public function getRendererName(): string
	{
		return 'MoneyPill';
	}
}
