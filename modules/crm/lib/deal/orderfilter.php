<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Binding;
use Bitrix\Crm\Order\DeliveryStage;
use Bitrix\Main;
use Bitrix\Sale\Internals;
use Bitrix\Sale\TradingPlatform;

Main\Loader::includeModule('sale');

final class OrderFilter
{
	public static function prepareFilter(array $filter): array
	{
		if (isset($filter['PAYMENT_STAGE']))
		{
			$filter['__CONDITIONS'][] = self::preparePaymentStageFilter($filter['PAYMENT_STAGE']);

			unset($filter['PAYMENT_STAGE']);
		}

		if (isset($filter['>=PAYMENT_PAID']) && isset($filter['<=PAYMENT_PAID']))
		{
			$filter['__CONDITIONS'][] = self::preparePaymentPaidFilter($filter['>=PAYMENT_PAID'], $filter['<=PAYMENT_PAID']);

			unset($filter['>=PAYMENT_PAID'], $filter['<=PAYMENT_PAID']);
		}

		if (isset($filter['ORDER_SOURCE']))
		{
			$filter['__CONDITIONS'][] = self::prepareOrderSourceFilter($filter['ORDER_SOURCE']);

			unset($filter['ORDER_SOURCE']);
		}

		if (isset($filter['DELIVERY_STAGE']) && is_array($filter['DELIVERY_STAGE']))
		{
			$filter['__CONDITIONS'][] = self::prepareDeliveryStageFilter($filter['DELIVERY_STAGE']);

			unset($filter['DELIVERY_STAGE']);
		}

		return $filter;
	}

	protected static function preparePaymentStageFilter(array $paymentStageList): array
	{
		$sql = self::convertEnumToSql($paymentStageList);

		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT bcops.PAYMENT_ID FROM ".Binding\OrderPaymentStageTable::getTableName()." bcops
				INNER JOIN ".Internals\PaymentTable::getTableName()." bsop ON bsop.ID = bcops.PAYMENT_ID
				INNER JOIN ".Binding\OrderDealTable::getTableName()." bcod ON bcod.ORDER_ID = bsop.ORDER_ID
				WHERE bcops.STAGE IN ({$sql}) AND bcod.DEAL_ID = ".\CCrmDeal::TABLE_ALIAS.".ID 
			)"
		];
	}

	protected static function preparePaymentPaidFilter(string $from, string $to): array
	{
		global $DB;

		$from = $DB->CharToDateFunction($from, 'SHORT', false);
		$to = $DB->CharToDateFunction($to, 'SHORT', false);

		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT ID FROM ".Internals\PaymentTable::getTableName()." bsop
				INNER JOIN ".Binding\OrderDealTable::getTableName()." bcod ON bcod.ORDER_ID = bsop.ORDER_ID
				WHERE bcod.DEAL_ID = ".\CCrmDeal::TABLE_ALIAS.".ID AND bsop.DATE_PAID >= {$from} AND bsop.DATE_PAID <= {$to}
			)"
		];
	}

	protected static function prepareOrderSourceFilter(array $orderSource): array
	{
		$sql = self::convertEnumToSql($orderSource);

		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT bstpo.ID FROM " . TradingPlatform\OrderTable::getTableName() . " bstpo
				INNER JOIN " . Binding\OrderDealTable::getTableName() . " bcod ON bcod.ORDER_ID = bstpo.ORDER_ID
				WHERE bstpo.TRADING_PLATFORM_ID IN ({$sql}) AND bcod.DEAL_ID = " . \CCrmDeal::TABLE_ALIAS . ".ID 
			)"
		];
	}

	protected static function prepareDeliveryStageFilter(array $deliveryStageList): array
	{
		$map = [
			DeliveryStage::SHIPPED => 'Y',
			DeliveryStage::NO_SHIPPED => 'N',
		];
		$enum = [];
		foreach ($deliveryStageList as $stage)
		{
			$enum[] = $map[$stage];
		}

		$sql = self::convertEnumToSql($enum);
		$deliveryTable = Internals\ShipmentTable::getTableName();
		$orderDealTable = Binding\OrderDealTable::getTableName();
		$dealTableAlias = \CCrmDeal::TABLE_ALIAS;

		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT 1 FROM {$deliveryTable} delivery
				INNER JOIN {$orderDealTable} orderdeal ON orderdeal.ORDER_ID = delivery.ORDER_ID
				WHERE delivery.SYSTEM = 'N'
					AND orderdeal.DEAL_ID = {$dealTableAlias}.ID
					AND delivery.DEDUCTED IN ({$sql})
			)",
		];
	}

	protected static function convertEnumToSql(array $list): string
	{
		array_walk(
			$list,
			function (&$value)
			{
				$helper = Main\Application::getConnection()->getSqlHelper();

				$value = "'".$helper->forSql($value)."'";
			}
		);

		return join(', ', $list);
	}
}
