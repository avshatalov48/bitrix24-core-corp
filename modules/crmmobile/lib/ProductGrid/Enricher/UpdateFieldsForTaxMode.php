<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Service\Accounting;
use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;

final class UpdateFieldsForTaxMode implements EnricherContract
{
	private Accounting $accounting;
	private Item $entity;

	public function __construct(Accounting $accounting, Item $entity)
	{
		$this->accounting = $accounting;
		$this->entity = $entity;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array
	{
		if (!$this->accounting->isTaxMode())
		{
			return $rows;
		}

		return array_map(function ($row) {

			$row->source = $this->rebuild($row->source, [
				'PRICE' => $row->source->getPriceExclusive(),
				'TAX_RATE' => 0,
				'TAX_INCLUDED' => 'N',
			]);

			return $row;

		}, $rows);
	}

	private function rebuild(ProductRow $source, array $mutations): ProductRow
	{
		$result = ProductRow::createFromArray(array_merge(
			$source->toArray(),
			$mutations
		));
		$this->entity->addToProductRows($result);
		return $result;
	}
}
