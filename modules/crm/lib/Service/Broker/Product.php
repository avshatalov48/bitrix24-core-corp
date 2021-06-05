<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Currency\Conversion;
use Bitrix\Crm\Discount;
use Bitrix\Crm\IBlockElementProxyTable;
use Bitrix\Crm\Measure;
use Bitrix\Crm\ProductRow;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Product
{
	private const REFERENCE_FIELD = 'IBLOCK_ELEMENT';

	/** @var IBlockElementProxyTable */
	private static $referenceDataClass = IBlockElementProxyTable::class;

	private static $errors = [];
	private static $measureInfo = [];

	public static function normalizeProduct(ProductRow $product, string $currencyId): Result
	{
		self::normalizeFields($product);
		self::fillPriceAccount($product, $currencyId);

		return self::compileResultForProduct($product);
	}

	private static function normalizeFields(ProductRow $product): void
	{
		self::normalizeProductName($product);
		self::normalizeMeasure($product);
		self::normalizeDiscount($product);
	}

	private static function normalizeProductName(ProductRow $product): void
	{
		$reference = $product->get(self::REFERENCE_FIELD);
		if (is_null($reference))
		{
			$reference = self::$referenceDataClass::getList([
				'select' => ['NAME'],
				'filter' => ['=ID' => $product->getProductId()],
			])->fetchObject();
			if (is_null($reference))
			{
				return;
			}
		}

		$referenceProductName = $reference->getName();
		if (!empty($referenceProductName) && $product->getProductName() === $referenceProductName)
		{
			$product->setProductName('');
		}
	}

	private static function normalizeMeasure(ProductRow $product): void
	{
		if (self::isValidMeasure($product->getMeasureCode(), $product->getMeasureName()))
		{
			return;
		}

		if (!$product->isNew())
		{
			$existingMeasureInfo = self::getExistingMeasureInfo($product->getId());
		}
		if (!empty($existingMeasureInfo))
		{
			$product->setMeasureCode($existingMeasureInfo['CODE']);
			$product->setMeasureName($existingMeasureInfo['SYMBOL']);

			return;
		}

		$defaultMeasureInfo = Measure::getDefaultMeasure();
		if (!empty($defaultMeasureInfo))
		{
			$product->setMeasureCode($defaultMeasureInfo['CODE']);
			$product->setMeasureName($defaultMeasureInfo['SYMBOL']);

			return;
		}

		self::addErrorForProduct(
			new Error(
			"Invalid measure. Default measure or existing measure in reference was not found. ID = {$product->getId()}"
			),
			$product
		);
	}

	private static function isValidMeasure(?int $measureCode, ?string $measureName): bool
	{
		if (!$measureCode || !$measureName)
		{
			return false;
		}

		$measure = Measure::getMeasureByCode($measureCode);
		// Such measure doesn't exists. Code is invalid
		if (empty($measure))
		{
			return false;
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		/** @noinspection OneTimeUseVariablesInspection */
		$isMeasureNameValid = $measure['SYMBOL'] === $measureName;

		return $isMeasureNameValid;
	}

	private static function getExistingMeasureInfo(int $productId): ?array
	{
		if (isset(self::$measureInfo[$productId]))
		{
			return self::$measureInfo[$productId];
		}

		$measureInfo = Measure::getProductMeasures($productId)[$productId] ?? null;
		self::$measureInfo[$productId] = $measureInfo;

		return $measureInfo;
	}

	private static function normalizeDiscount(ProductRow $product): void
	{
		if (!Discount::isDefined($product->getDiscountTypeId()))
		{
			$product->setDiscountTypeId(Discount::PERCENTAGE);
			$product->setDiscountRate(0.0);
		}

		if ($product->getDiscountTypeId() === Discount::PERCENTAGE)
		{
			self::normalizeDiscountForPercentage($product);
		}
		elseif ($product->getDiscountTypeId() === Discount::MONETARY)
		{
			self::normalizeDiscountForMonetary($product);
		}
	}

	private static function normalizeDiscountForPercentage(ProductRow $product): void
	{
		if (is_null($product->getDiscountRate()))
		{
			self::addErrorForProduct(
				new Error(
				"Discount Rate (DISCOUNT_RATE) is required if Percentage Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$product->getId()}"
				),
				$product
			);
			return;
		}

		if (is_null($product->getDiscountSum()))
		{
			$discountSum = Discount::calculateDiscountSum($product->getPriceExclusive(), $product->getDiscountRate());
			$product->setDiscountSum($discountSum);
		}
	}

	private static function normalizeDiscountForMonetary(ProductRow $product): void
	{
		if (is_null($product->getDiscountSum()))
		{
			self::addErrorForProduct(
				new Error(
				"Discount Sum (DISCOUNT_SUM) is required if Monetary Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$product->getId()}"
				),
				$product
			);
			return;
		}

		if (is_null($product->getDiscountRate()))
		{
			$priceBeforeDiscount = $product->getPriceExclusive() + $product->getDiscountSum();
			$discountRate = Discount::calculateDiscountRate($priceBeforeDiscount, $product->getPriceExclusive());
			$product->setDiscountRate($discountRate);
		}
	}

	private static function fillPriceAccount(ProductRow $product, string $currencyId): void
	{
		$priceAccount = Conversion::toAccountCurrency($product->getPrice(), $currencyId);
		$product->setPriceAccount($priceAccount);
	}

	private static function compileResultForProduct(ProductRow $product): Result
	{
		$result = new Result();
		$errors = self::getErrorsForProduct($product);
		if ($errors)
		{
			$result->addErrors($errors);
		}

		return $result;
	}

	/**
	 * Add the error that was triggered by the specific product
	 *
	 * @param Error $error
	 * @param ProductRow $product
	 */
	private static function addErrorForProduct(Error $error, ProductRow $product): void
	{
		self::$errors[self::calculateProductHash($product)][] = $error;
	}

	/**
	 * Get all errors that were triggered by the specific product
	 *
	 * @param ProductRow $product
	 *
	 * @return array
	 */
	private static function getErrorsForProduct(ProductRow $product): array
	{
		return self::$errors[self::calculateProductHash($product)] ?? [];
	}

	private static function calculateProductHash(ProductRow $product): string
	{
		$scalarValues = array_filter($product->collectValues(), static function($value){
			return !is_object($value);
		});

		return implode($scalarValues);
	}
}