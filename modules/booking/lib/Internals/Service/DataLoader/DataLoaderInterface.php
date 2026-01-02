<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DataLoader;

use Bitrix\Booking\Entity\BaseEntityCollection;

interface DataLoaderInterface
{
	public function loadForCollection(BaseEntityCollection $collection): void;
}
