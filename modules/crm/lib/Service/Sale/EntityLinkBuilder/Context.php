<?php

namespace Bitrix\Crm\Service\Sale\EntityLinkBuilder;

/**
 * Class Context
 *
 * @package Bitrix\Crm\Service\Sale\EntityLinkBuilder
 */
class Context
{
	/** @var bool */
	private bool $isShopArea = false;

	/** @var bool */
	private bool $isShopLinkForced = false;

	/**
	 * @return static
	 */
	public static function getShopAreaContext(): self
	{
		return (new self)->setIsShopArea(true);
	}

	/**
	 * @return static
	 */
	public static function getShopForcedContext(): self
	{
		return (new self)->setIsShopLinkForced(true);
	}

	/**
	 * @return bool
	 */
	public function isShopArea(): bool
	{
		return $this->isShopArea;
	}

	/**
	 * @param bool $isShopArea
	 * @return Context
	 */
	public function setIsShopArea(bool $isShopArea): Context
	{
		$this->isShopArea = $isShopArea;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isShopLinkForced(): bool
	{
		return $this->isShopLinkForced;
	}

	/**
	 * @param bool $isShopLinkForced
	 * @return Context
	 */
	public function setIsShopLinkForced(bool $isShopLinkForced): Context
	{
		$this->isShopLinkForced = $isShopLinkForced;
		return $this;
	}
}
