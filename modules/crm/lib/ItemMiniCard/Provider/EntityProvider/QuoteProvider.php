<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;

final class QuoteProvider extends AbstractEntityProvider
{
	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('commercial-offer');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->getStage(),
			$this->fieldFactory->getProducts(),
			$this->fieldFactory->getOpportunity(),
			$this->fieldFactory->getCompany(),
			$this->fieldFactory->getContact(),
		];
	}
}
