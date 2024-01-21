<?php

namespace Bitrix\Crm\Reservation\Helpers;

use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Main\Application;
use Bitrix\Main\Text\HtmlFilter;

/**
 * Doctor for inventory management.
 *
 * @internal use it only for debug!
 *
 * This object does not perform any edits, because there can be many reasons for any problem
 * and you need to understand them individually.
 *
 * Example show summary info:
 * ```php
	\Bitrix\Main\Loader::includeModule('crm');

	$doctor = new \Bitrix\Crm\Reservation\Helpers\Doctor();
	$doctor->printInfo();
	// or show only problems
	$doctor->printProblems();
 * ```
 */
final class Doctor
{
	/**
	 * @return array[]
	 */
	private function getRows(): array
	{
		$result = [];

		$db = Application::getConnection();
		$helper = $db->getSqlHelper();

		// load crm reserves
		$crmReservesSql = '
			SELECT
				cprr.ROW_ID,
				cpr.OWNER_ID,
				cpr.OWNER_TYPE,
				cpr.PRODUCT_ID,
				cpr.PRODUCT_NAME,
				cprr.STORE_ID AS CRM_RESERVE_STORE_ID,
				cprr.RESERVE_QUANTITY AS CRM_RESERVE_QUANTITY,
				' . $helper->getDatetimeToDateFunction('cprr.DATE_RESERVE_END') . ' AS CRM_RESERVE_DATE_END,
				sbr.BASKET_ID
			FROM
				b_crm_product_row_reservation as cprr
				LEFT JOIN b_crm_product_reservation_map AS cprm ON cprr.ROW_ID = cprm.PRODUCT_ROW_ID
				LEFT JOIN b_sale_basket_reservation AS sbr ON cprm.BASKET_RESERVATION_ID = sbr.ID
				LEFT JOIN b_crm_product_row AS cpr ON cpr.ID = cprr.ROW_ID
		';
		$rows = $db->query($crmReservesSql);
		foreach ($rows as $row)
		{
			$rowId = (int)$row['ROW_ID'];

			$row['CRM_RESERVE_STORE_ID'] = (int)$row['CRM_RESERVE_STORE_ID'];
			$row['CRM_RESERVE_QUANTITY'] = (float)$row['CRM_RESERVE_QUANTITY'];
			$row['CRM_RESERVE_DATE_END'] = (string)$row['CRM_RESERVE_DATE_END'];

			$result[$rowId] = $row;
			$result[$rowId] += [
				'SALE_RESERVE_DATE_END' => '',
				'SALE_RESERVE_STORE_ID' => 0,
				'SALE_RESERVE_QUANTITY' => 0.0,
				'SALE_DEDUCTED_QUANTITY' => 0.0,
			];
		}

		if (empty($result))
		{
			return [];
		}

		// load sale reserves and deductes
		$basketToRowIds = BasketService::getInstance()->getRowIdsToBasketIdsByRows(array_keys($result));
		if (!empty($basketToRowIds))
		{
			$basketIdsSql = join(',', $basketToRowIds);
			$basketToRowIds = array_flip($basketToRowIds);

			$saleReservesSql = "
				SELECT
					BASKET_ID,
					STORE_ID AS SALE_RESERVE_STORE_ID,
					QUANTITY AS SALE_RESERVE_QUANTITY,
					" . $helper->getDatetimeToDateFunction('DATE_RESERVE_END') . " AS SALE_RESERVE_DATE_END
				FROM
					b_sale_basket_reservation
				WHERE
					BASKET_ID IN ({$basketIdsSql})
			";

			$rows = $db->query($saleReservesSql);
			foreach ($rows as $row)
			{
				$basketId = (int)$row['BASKET_ID'];
				$rowId = $basketToRowIds[$basketId] ?? null;
				if ($rowId !== null)
				{
					$result[$rowId]['SALE_RESERVE_STORE_ID'] = (int)$row['SALE_RESERVE_STORE_ID'];
					$result[$rowId]['SALE_RESERVE_QUANTITY'] = (float)$row['SALE_RESERVE_QUANTITY'];
					$result[$rowId]['SALE_RESERVE_DATE_END'] = (string)$row['SALE_RESERVE_DATE_END'];
				}
			}

			$saleDeductedSql = "
				SELECT
					sodb.BASKET_ID,
					SUM(sodb.QUANTITY) as SALE_DEDUCTED_QUANTITY
				FROM
					b_sale_order_dlv_basket AS sodb
					INNER JOIN b_sale_order_delivery AS sod ON sodb.ORDER_DELIVERY_ID = sod.ID AND sod.DEDUCTED = 'Y'
				WHERE
					sodb.BASKET_ID IN ({$basketIdsSql})
				GROUP BY
					sodb.BASKET_ID
			";
			$rows = $db->query($saleDeductedSql);
			foreach ($rows as $row)
			{
				$basketId = (int)$row['BASKET_ID'];
				$rowId = $basketToRowIds[$basketId] ?? null;
				if ($rowId !== null)
				{
					$result[$rowId]['SALE_DEDUCTED_QUANTITY'] = (float)$row['SALE_DEDUCTED_QUANTITY'];
				}
			}
		}

		// sort columns
		$sortOrder = array_flip([
			'ROW_ID',
			'OWNER_ID',
			'OWNER_TYPE',
			'PRODUCT_ID',
			'PRODUCT_NAME',
			'BASKET_ID',
			'CRM_RESERVE_STORE_ID',
			'SALE_RESERVE_STORE_ID',
			'CRM_RESERVE_DATE_END',
			'SALE_RESERVE_DATE_END',
			'CRM_RESERVE_QUANTITY',
			'SALE_RESERVE_QUANTITY',
			'SALE_DEDUCTED_QUANTITY',
		]);

		foreach ($result as &$row)
		{
			uksort(
				$row,
				static fn($a, $b) => $sortOrder[$a] <=> $sortOrder[$b]
			);
		}
		unset($row);

		return $result;
	}

	/**
	 * Print info about crm and sale actual quantities.
	 *
	 * @return void
	 */
	public function printInfo(): void
	{
		$this->printTable(
			$this->getRows()
		);
	}

	/**
	 * Print crm and sale problems.
	 *
	 * @return void
	 */
	public function printProblems(): void
	{
		$result = [];

		$rows = $this->getRows();
		foreach ($rows as $row)
		{
			$problems = [];

			if (empty($row['OWNER_ID']))
			{
				$problems[] = 'Product row is deleted';
			}
			else
			{
				$isAllDeducted = $row['SALE_DEDUCTED_QUANTITY'] === $row['CRM_RESERVE_QUANTITY'];
				if ($isAllDeducted)
				{
					continue;
				}

				if ($row['CRM_RESERVE_STORE_ID'] !== $row['SALE_RESERVE_STORE_ID'])
				{
					$problems[] = 'Store are not equal';
				}

				if ($row['CRM_RESERVE_DATE_END'] !== $row['SALE_RESERVE_DATE_END'])
				{
					$problems[] = 'Date end are not equal';
				}

				// because crm not clean reserves after deduct shipment.
				$saleQuanity = $row['SALE_RESERVE_QUANTITY'] + $row['SALE_DEDUCTED_QUANTITY'];
				$crmQuantity = $row['CRM_RESERVE_QUANTITY'];
				if ($crmQuantity !== $saleQuanity)
				{
					$isWithReserves =
						$row['CRM_RESERVE_QUANTITY'] !== 0.0
						|| $row['SALE_RESERVE_QUANTITY'] !== 0.0
					;
					if ($isWithReserves)
					{
						$problems[] = 'Quantities not equal';
					}
				}
			}

			if (empty($problems))
			{
				continue;
			}

			$result[] = ['PROBLEMS' => join('; ', $problems)] + $row;
		}

		$this->printTable($result);
	}

	/**
	 * @param array[] $rows
	 *
	 * @return void
	 */
	private function printTable(array $rows): void
	{
		if (empty($rows))
		{
			echo '-- empty --';
			return;
		}

		$headers = array_keys(
			current($rows)
		);

		echo '<table border="1" cellspacing="0" cellpadding="2"><tr>';
		foreach ($headers as $header)
		{
			echo '<th>' . HtmlFilter::encode($header) . '</th>';
		}

		foreach ($rows as $row)
		{
			echo '<tr>';

			foreach ($headers as $header)
			{
				$value = isset($row[$header]) && $row[$header] !== '' ? $row[$header] : '-';
				echo '<td>' . HtmlFilter::encode($value) . '</td>';
			}

			echo '</td>';
		}

		echo '</table>';
	}
}
