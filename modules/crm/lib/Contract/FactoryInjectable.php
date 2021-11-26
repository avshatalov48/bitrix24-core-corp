<?php

namespace Bitrix\Crm\Contract;

use Bitrix\Crm\Service\Factory;

interface FactoryInjectable
{
	/**
	 * Set an instance of factory that will be used in this object
	 *
	 * @param Factory $factory
	 *
	 * @return void
	 */
	public function setFactory(Factory $factory): void;

	/**
	 * Get an instance of factory that is used in this object
	 *
	 * @return Factory|null
	 */
	public function getFactory(): ?Factory;
}
