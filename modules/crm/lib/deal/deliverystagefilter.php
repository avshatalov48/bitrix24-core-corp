<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order\DeliveryStage;

/**
 * Class helps to filter deals by flag DEDUCTED in related shipments
 */
class DeliveryStageFilter
{
	/**
	 * @var string[]
	 * @see Bitrix\Crm\Order\DeliveryStage constants
	 */
	protected $stages = [];

	/**
	 * @param string[] $stages
	 */
	public function __construct(array $stages = [])
	{
		$this->stages = $stages;	
	}

	/**
	 * Returns prepared SQL-query or empty string if no valid stages specified
	 * @return string
	 */
	public function getDealIdQuery(): string
	{
		$options = $this->mapStages();
	
		if (count($options) > 0)
		{
			$strOptions = implode(',', $options);
	
			// we use plain SQL instead of ORM 
			// because we have to search deals by flag DEDUCTED in _latest_ non-system shipment
			return <<<SQL
SELECT OD.DEAL_ID AS ID
FROM b_crm_order_deal OD
	INNER JOIN b_sale_order_delivery DLV ON OD.ORDER_ID = DLV.ORDER_ID
	INNER JOIN (
		SELECT O.DEAL_ID, max(D.ID) AS SHIPMENT_ID
		FROM b_crm_order_deal O
			INNER JOIN b_sale_order_delivery D ON O.ORDER_ID = D.ORDER_ID
		GROUP BY O.DEAL_ID
	) AS BUF ON OD.DEAL_ID = BUF.DEAL_ID AND DLV.ID = BUF.SHIPMENT_ID
WHERE DLV.DEDUCTED IN ($strOptions) AND DLV.SYSTEM = "N"
SQL;
		}

		return '';
	}

	/**
	 * Converts stages into SQL-ready Y/N flags
	 * @return string[]
	 */
	protected function mapStages(): array
	{
		$map = [
			DeliveryStage::SHIPPED => '"Y"',
			DeliveryStage::NO_SHIPPED => '"N"',
		];
		$options = [];
		foreach ($this->stages as $value)
		{
			if (isset($map[$value]))
			{
				$options[] = $map[$value];
			}
		}
		return $options;
	}
}
