<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;

final class DealProvider extends AbstractEntityProvider
{
	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('o-handshake');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->getStage(),
			$this->fieldFactory->getProducts(),
			$this->fieldFactory->getOpportunity(),
			$this->fieldFactory->get(Deal::FIELD_NAME_PROBABILITY),
			$this->fieldFactory->getCompany(),
			$this->fieldFactory->getContact(),
		];
	}
}
