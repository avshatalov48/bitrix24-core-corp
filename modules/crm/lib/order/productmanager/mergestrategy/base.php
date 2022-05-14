<?php

namespace Bitrix\Crm\Order\ProductManager\MergeStrategy;

use Bitrix\Crm;

abstract class Base
{
	use Crm\Order\ProductManager\ProductFinder;

	/** @var Crm\Order\Order $order */
	private $order;

	public function __construct(?Crm\Order\Order $order)
	{
		$this->order = $order;
	}

	protected function getOrder(): ?Crm\Order\Order
	{
		return $this->order;
	}

	/**
	 * @param $orderProducts
	 * @param $dealProducts
	 * @return array
	 */
	abstract public function mergeProducts($orderProducts, $dealProducts): array;
}
