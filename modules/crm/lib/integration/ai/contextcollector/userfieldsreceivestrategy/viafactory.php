<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\UserFieldsReceiveStrategy;

use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\AI\ContextCollector\UserFieldsReceiveStrategy;
use Bitrix\Crm\Service\Factory;

final class ViaFactory implements UserFieldsReceiveStrategy
{
	public function __construct(
		private readonly ?Factory $factory,
	)
	{
	}

	public function getAll(): Collection
	{
		return $this->factory?->getUserFieldsCollection() ?? new Collection();
	}
}
