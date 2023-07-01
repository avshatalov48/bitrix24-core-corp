<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Binding;
use Bitrix\Crm\Order\DeliveryStage;
use Bitrix\Crm\Workflow\EntityStageTable;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Main;
use Bitrix\Sale\Internals;
use Bitrix\Sale\TradingPlatform;
use Bitrix\Sale\Delivery;

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
				SELECT stage.ENTITY_ID FROM ".EntityStageTable::getTableName()." stage
				INNER JOIN ".Internals\PaymentTable::getTableName()." payment ON payment.ID = stage.ENTITY_ID
				INNER JOIN ".Binding\OrderEntityTable::getTableName()." orderentity ON orderentity.ORDER_ID = payment.ORDER_ID
				WHERE stage.WORKFLOW_CODE = '" . PaymentWorkflow::getWorkflowCode() . "' 
				AND stage.STAGE IN ({$sql}) AND orderentity.OWNER_ID = ".\CCrmDeal::TABLE_ALIAS.".ID AND orderentity.OWNER_TYPE_ID=".\CCrmOwnerType::Deal."
			)"
		];
	}

	protected static function preparePaymentPaidFilter(string $from, string $to): array
	{
		global $DB;

		$from = $DB->CharToDateFunction($from);
		$to = $DB->CharToDateFunction($to);

		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT ID FROM ".Internals\PaymentTable::getTableName()." bsop
				INNER JOIN ".Binding\OrderEntityTable::getTableName()." bcod ON bcod.ORDER_ID = bsop.ORDER_ID
				WHERE bcod.OWNER_ID = ".\CCrmDeal::TABLE_ALIAS.".ID AND bcod.OWNER_TYPE_ID=".\CCrmOwnerType::Deal." AND bsop.DATE_PAID >= {$from} AND bsop.DATE_PAID <= {$to}
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
				INNER JOIN " . Binding\OrderEntityTable::getTableName() . " bcod ON bcod.ORDER_ID = bstpo.ORDER_ID
				WHERE bstpo.TRADING_PLATFORM_ID IN ({$sql}) AND bcod.OWNER_ID = " . \CCrmDeal::TABLE_ALIAS . ".ID AND bcod.OWNER_TYPE_ID=".\CCrmOwnerType::Deal."
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
		$shipmentTable = Internals\ShipmentTable::getTableName();
		$deliveryTable = Delivery\Services\Table::getTableName();
		$orderEntityTable = Binding\OrderEntityTable::getTableName();
		$dealTableAlias = \CCrmDeal::TABLE_ALIAS;

		$helper = Main\Application::getConnection()->getSqlHelper();


		return [
			"TYPE" => "WHERE",
			"SQL" => "EXISTS (
				SELECT 1 FROM {$shipmentTable} shipment
				INNER JOIN {$orderEntityTable} orderentity ON orderentity.ORDER_ID = shipment.ORDER_ID
				INNER JOIN {$deliveryTable} delivery ON delivery.ID = shipment.DELIVERY_ID
				WHERE shipment.SYSTEM = 'N'
					AND orderentity.OWNER_ID = {$dealTableAlias}.ID
					AND orderentity.OWNER_TYPE_ID = " . \CCrmOwnerType::Deal . "
					AND shipment.DEDUCTED IN ({$sql})
					AND delivery.CLASS_NAME != '" . $helper->forSql('\\' . Delivery\Services\EmptyDeliveryService::class) . "'
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
