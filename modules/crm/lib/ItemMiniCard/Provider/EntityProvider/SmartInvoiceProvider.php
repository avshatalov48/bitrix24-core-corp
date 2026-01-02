<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;

final class SmartInvoiceProvider extends DynamicProvider
{
	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('o-invoice');
	}
}
