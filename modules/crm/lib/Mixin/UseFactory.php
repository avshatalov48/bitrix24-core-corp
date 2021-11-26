<?php

namespace Bitrix\Crm\Mixin;

use Bitrix\Crm\Contract\CanUseFactory;
use Bitrix\Crm\Service\Factory;

/**
 * Trait UseFactory
 *
 * Class that uses this trait uses @see Factory and a concrete instance of Factory could be set externaly.
 *
 * Can be used to implement interface @see CanUseFactory
 */
trait UseFactory
{
	/** @var Factory|null */
	protected $factory;

	/**
	 * Returns an instance of Factory that is used in this object
	 *
	 * @return Factory|null
	 */
	public function getFactory(): ?Factory
	{
		return $this->factory;
	}

	/**
	 * Set an instance of Factory that should be used in this object
	 *
	 * @param Factory $factory
	 *
	 * @return void
	 */
	public function setFactory(Factory $factory): void
	{
		$this->factory = $factory;
	}
}
