<?php

namespace Bitrix\Crm\Contract;

use Bitrix\Crm\Service\Factory;

/**
 * Interface CanUseFactory
 *
 * Class that implements this interface uses @see Factory and a concreate instance of factory can be set externaly
 */
interface CanUseFactory
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
