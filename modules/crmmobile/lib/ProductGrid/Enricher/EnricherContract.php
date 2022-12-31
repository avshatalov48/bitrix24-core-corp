<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;

interface EnricherContract
{
	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array;
}
