<?php

namespace Bitrix\Crm\Service\Timeline\Item\Catalog;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Main\Loader;

class ProductCompilation extends Configurable
{
	private array $data;

	/**
	 * @inheritDoc
	 */
	public function getType(): string
	{
		return 'ProductCompilation';
	}

	public function setData(array $data): self
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array
	{
		$data = $this->data ?? [];

		$can = true;
		if (Loader::includeModule('catalog'))
		{
			$can = AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
		}

		$data['showProductLink'] = $can;
		$data['canAddProductToDeal'] = $can;

		return $data;
	}
}
