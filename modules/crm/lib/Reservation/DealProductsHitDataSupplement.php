<?php

namespace Bitrix\Crm\Reservation;

class DealProductsHitDataSupplement
{
	/** @var array */
	private $data = [];

	/** @var DealProductsHitDataSupplement */
	private static $instance;

	/**
	 * DealProductsHitDataSupplement constructor.
	 */
	private function __construct()
	{}

	/**
	 * @return DealProductsHitDataSupplement
	 */
	public static function getInstance(): DealProductsHitDataSupplement
	{
		if (is_null(static::$instance))
		{
			static::$instance = new DealProductsHitDataSupplement();
		}

		return static::$instance;
	}

	/**
	 * @param int $dealId
	 * @param array $productRows
	 */
	public function setProductRows(int $dealId, array $productRows): void
	{
		$this->data[$dealId] = [
			'PRODUCT_ROWS' => $productRows,
		];
	}

	/**
	 * @param int $dealId
	 */
	public function readDealSaveData(int $dealId)
	{
		$this->data[$dealId] = [
			'SAFE_ORIGINAL_ROWS' => \CCrmProductRow::getOriginalRows(),
		];
	}

	/**
	 * @param int $dealId
	 * @return array|null
	 */
	public function getSupplementedProductRows(int $dealId): ?array
	{
		if (!isset($this->data[$dealId]))
		{
			return null;
		}
		$dealData = $this->data[$dealId];

		if (isset($dealData['SAFE_ORIGINAL_ROWS']))
		{
			$result = [];

			foreach ($dealData['SAFE_ORIGINAL_ROWS'] as $safeOriginalRow)
			{
				$enrichedProductRow = $safeOriginalRow['ORIGINAL_ROW'];
				$enrichedProductRow['ID'] = $safeOriginalRow['ID'];
				$result[] = $enrichedProductRow;
			}

			return $result;
		}

		if (isset($dealData['PRODUCT_ROWS']) && !empty($dealData['PRODUCT_ROWS']))
		{
			return $dealData['PRODUCT_ROWS'];
		}

		return null;
	}
}
