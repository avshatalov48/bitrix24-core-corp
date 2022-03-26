<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;

class Accounting
{
	protected $isTaxMode = false;
	protected $cache = [];

	public function __construct()
	{
		$this->isTaxMode = \CCrmTax::isTaxMode();
	}

	/**
	 * Return true if location dependant tax mode is used.
	 * By default VAT taxes used.
	 *
	 * @return bool
	 */
	public function isTaxMode(): bool
	{
		return $this->isTaxMode;
	}

	/**
	 * Switch location dependant tax mode.
	 *
	 * @param bool $isTaxMode
	 * @return $this
	 */
	public function setTaxMode(bool $isTaxMode): self
	{
		$this->isTaxMode = $isTaxMode;

		return $this;
	}

	/**
	 * Clear inner calculation cache.
	 *
	 * @return $this
	 */
	public function clearCache(): self
	{
		$this->cache = [];

		return $this;
	}

	private function isResultCached(Item $item): bool
	{
		if ($item->isNew())
		{
			return false;
		}

		$hash = $this->compileHash($item);

		return isset($this->cache[$hash]);
	}

	private function compileHash(Item $item): string
	{
		$ownerHash = serialize($item->getData());

		$productRows = $item->getProductRows() ? $item->getProductRows()->toArray() : [];
		$productRowsHash = serialize($productRows);

		// return result from cache only if a current item is exactly the same as previous
		return md5($ownerHash . $productRowsHash);
	}

	private function getResultFromCache(Item $item): Accounting\Result
	{
		if ($item->isNew())
		{
			throw new ArgumentException('Accounting result caching for new items is not possible');
		}

		$hash = $this->compileHash($item);

		$result = $this->cache[$hash] ?? null;
		if (!$result)
		{
			throw new InvalidOperationException('Result for this item is not cached yet');
		}

		return $result;
	}

	private function cacheResult(Item $item, Accounting\Result $result): void
	{
		if ($item->isNew())
		{
			return;
		}

		$hash = $this->compileHash($item);

		$this->cache[$hash] = $result;
	}

	/**
	 * Calculate total sums based on current tax mode and $item`s fields.
	 * This method stores calculation result in inner cache.
	 *
	 * @param Item $item
	 * @return Accounting\Result
	 */
	public function calculateByItem(Item $item): Accounting\Result
	{
		if ($this->isResultCached($item))
		{
			return $this->getResultFromCache($item);
		}

		$productRows = $item->getProductRows() ? $item->getProductRows()->toArray() : [];
		$personTypeId = $this->resolvePersonTypeId($item);

		$locationId = null;
		if ($item->hasField(Item::FIELD_NAME_LOCATION_ID))
		{
			$locationId = $item->get(Item::FIELD_NAME_LOCATION_ID);
		}

		$result = Accounting\Result::initializeFromArray(
			$this->calculate($productRows, $item->getCurrencyId(), $personTypeId, $locationId)
		);

		$this->cacheResult($item, $result);

		return $result;
	}

	/**
	 * Calculates total sums based on current tax mode and provided data.
	 * @see \CCrmSaleHelper::Calculate
	 *
	 * @param array $productRows
	 * @param string $currencyId
	 * @param int $personTypeId
	 * @param string|null $locationId
	 * @return array|null
	 */
	public function calculate(
		array $productRows,
		string $currencyId,
		int $personTypeId,
		?string $locationId = null
	): ?array
	{
		$options = [
			'ALLOW_LD_TAX' => 'N',
		];
		if ($this->isTaxMode())
		{
			$options['ALLOW_LD_TAX'] = 'Y';
			if (!empty($locationId))
			{
				$options['LOCATION_ID'] = $locationId;
			}
		}

		return \CCrmSaleHelper::Calculate(
			$productRows,
			$currencyId,
			$personTypeId,
			false,
			SITE_ID,
			$options
		);
	}

	public function calculateDeliveryTotal(ItemIdentifier $itemIdentifier): float
	{
		$orderIds = \Bitrix\Crm\Binding\OrderEntityTable::getOrderIdsByOwner(
			$itemIdentifier->getEntityId(),
			$itemIdentifier->getEntityTypeId(),
		);

		$orders = Container::getInstance()->getOrderBroker()->getBunchByIds($orderIds);

		$total = 0;
		foreach ($orders as $order)
		{
			$total += $order->getShipmentCollection()->getPriceDelivery();
		}

		return (float)$total;
	}

	/**
	 * Returns person type id based on data contained in the $item
	 *
	 * @param Item $item
	 *
	 * @return int - if a suitable person type was not found, returns 0
	 */
	public function resolvePersonTypeId(Item $item): int
	{
		$personTypes = \CCrmPaySystem::getPersonTypeIDs();

		if (isset($personTypes['COMPANY']) && ($item->getCompanyId() > 0))
		{
			return (int)$personTypes['COMPANY'];
		}
		if (isset($personTypes['CONTACT']))
		{
			return (int)$personTypes['CONTACT'];
		}

		return 0;
	}

	/**
	 * Returns original price before tax
	 *
	 * @param float $priceWithTax
	 * @param float $taxRate
	 *
	 * @return float
	 */
	public function calculatePriceWithoutTax(float $priceWithTax, float $taxRate): float
	{
		return \CCrmProductRow::CalculateExclusivePrice($priceWithTax, $taxRate);
	}

	/**
	 * Applies tax with $taxRate to the price and returns its new value
	 *
	 * @param float $priceWithoutTax
	 * @param float $taxRate
	 *
	 * @return float
	 */
	public function calculatePriceWithTax(float $priceWithoutTax, float $taxRate): float
	{
		return \CCrmProductRow::CalculateInclusivePrice($priceWithoutTax, $taxRate);
	}
}
