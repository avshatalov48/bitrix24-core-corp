<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Crm;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Type\Date;

class ProductRowReservation extends Crm\Reservation\Internals\EO_ProductRowReservation
{
	public const PRODUCT_ROW_NAME = 'PRODUCT_ROW';

	public const ID = 'ID';
	public const ROW_ID = 'ROW_ID';
	public const RESERVE_ID = 'RESERVE_ID';
	public const RESERVE_QUANTITY = 'RESERVE_QUANTITY';
	public const RESERVE_STORE_ID = 'STORE_ID';
	public const RESERVE_DATE_END = 'DATE_RESERVE_END';
	public const IS_AUTO = 'IS_AUTO';

	private ?Crm\ProductRow $productRow = null;
	private ?int $saleReserveId = null;

	public static function create(Crm\ProductRow $productRow, array $fields): ProductRowReservation
	{
		$fields = self::prepareFields($productRow, $fields);

		$reserveObject = null;
		if (isset($fields[self::ROW_ID]))
		{
			$reserveObject = self::loadProductRowReservationObject($fields);
		}

		if (!$reserveObject)
		{
			$reserveObject = new static($fields);
		}

		$reserveObject->productRow = $productRow;

		return $reserveObject;
	}

	private static function loadProductRowReservationObject(array $productRowReservation): ?ProductRowReservation
	{
		/** @var ProductRowReservation $reserveObject */
		$reserveObject = Crm\Reservation\Internals\ProductRowReservationTable::getList([
			'filter' => [
				'=ROW_ID' => $productRowReservation['ROW_ID'],
			],
			'select' => [self::ID],
			'limit' => 1,
		])->fetchObject();

		if ($reserveObject)
		{
			foreach ($productRowReservation as $name => $value)
			{
				$reserveObject->set($name, $value);
			}
		}

		return $reserveObject;
	}

	private static function prepareFields(Crm\ProductRow $productRow, array $fields): array
	{
		if (isset($fields[self::ID]))
		{
			unset($fields['ID']);
		}

		if ($productRow->getId() > 0)
		{
			$fields['ROW_ID'] = $productRow->getId();
		}

		if (isset($fields['INPUT_RESERVE_QUANTITY']))
		{
			$fields['RESERVE_QUANTITY'] = $fields['INPUT_RESERVE_QUANTITY'];
		}

		if (!array_key_exists('DATE_RESERVE_END', $fields))
		{
			$fields['DATE_RESERVE_END'] = ReservationService::getInstance()->getDefaultDateReserveEnd();
		}

		if (is_int($fields['DATE_RESERVE_END']))
		{
			$fields['DATE_RESERVE_END'] = Date::createFromPhp(
				(new \DateTime())->setTimestamp($fields['DATE_RESERVE_END'])
			);
		}

		return array_filter(
			$fields,
			static function (string $fieldName): bool {
				return in_array($fieldName, static::getScalarFieldNames(), true);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	private static function getScalarFieldNames(): array
	{
		$entity = Crm\Reservation\Internals\ProductRowReservationTable::getEntity();

		$result = [];
		foreach ($entity->getScalarFields() as $scalarField)
		{
			$result[] = $scalarField->getName();
		}

		return $result;
	}

	public function getReserveId(): ?int
	{
		if (!$this->saleReserveId)
		{
			if (!$this->productRow)
			{
				$this->loadProduct();
			}

			$this->loadSaleReserve();
		}

		return $this->saleReserveId;
	}

	public function isNew(): bool
	{
		return (!($this->getId() > 0));
	}

	public function toArray(): array
	{
		return [
			self::RESERVE_ID => $this->getReserveId(),
			self::RESERVE_QUANTITY => $this->getReserveQuantity(),
			self::RESERVE_STORE_ID => $this->getStoreId(),
			self::RESERVE_DATE_END => $this->getDateReserveEnd(),
			self::IS_AUTO => $this->getIsAuto(),
		];
	}

	private function loadSaleReserve(): void
	{
		$productRowId = $this->productRow ? $this->productRow->getId() : 0;
		if ($productRowId > 0)
		{
			$basketReservation = new BasketReservation();
			$basketReservation->addProduct([
				'ID' => $productRowId,
			]);
			$reservedProducts = $basketReservation->getReservedProducts();

			if (isset($reservedProducts[$productRowId]))
			{
				$reserveFields = $reservedProducts[$productRowId];
				$this->saleReserveId = (int)$reserveFields[self::RESERVE_ID];
			}
		}
	}

	private function loadProduct(): void
	{
		/** @var Crm\ProductRow $productRow */
		$this->productRow = $this->getProductRow();
		if (!$this->productRow)
		{
			$productRowId = (int)$this->getRowId();
			if ($productRowId > 0)
			{
				$this->productRow = Crm\ProductRowTable::getByPrimary($productRowId)->fetchObject();
			}
		}
	}
}
