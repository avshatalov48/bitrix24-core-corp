<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;

class DynamicProvider extends AbstractEntityProvider
{
	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('o-smart-process');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->getStage(),
			$this->fieldFactory->getOpportunity(),
		];
	}
}
