<?php

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Crm\Item;
use Bitrix\Main\Loader;

Loader::includeModule('catalog');

class CompleteBasketFieldsForNewProduct implements EnricherContract
{
	private Item $entity;

	public function __construct(Item $entity)
	{
		$this->entity = $entity;
	}

	/**
	 * @inheritDoc
	 */
	public function enrich(array $rows): array
	{
		$result = [];

		foreach ($rows as $row)
		{
			$basketItemFields = [
				'XML_ID' => uniqid('', true),
				'MODULE' => 'catalog',
			];
			$row->basketItemFields = $basketItemFields;

			$result[] = $row;
		}

		return $result;
	}
}
