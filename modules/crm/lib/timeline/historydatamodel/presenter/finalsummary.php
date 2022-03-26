<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Order\Manager;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

class FinalSummary extends Presenter
{
	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$culture = Context::getCurrent()->getCulture();

		$data['RESULT'] = [];
		if (!isset($settings['ORDER_IDS']) || !is_array($settings['ORDER_IDS']))
		{
			return $data;
		}
		foreach ($settings['ORDER_IDS'] as $orderId)
		{
			$order = Order::load($orderId);
			if (!$order)
			{
				continue;
			}

			$row['PAYMENTS'] = [];

			foreach ($order->getPaymentCollection() as $payment)
			{
				if ($payment->isPaid())
				{
					$row['PAYMENTS'][] = [
						'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
							$payment->getField('SUM'),
							$order->getCurrency()
						),
						'DATE_PAID' => FormatDate($culture->getLongDateFormat(), $payment->getField('DATE_PAID')->getTimestamp()),
					];
				}
			}

			$row['ORDER'] = [
				'TITLE' => Loc::getMessage(
					'CRM_ORDER_TITLE',
					[
						'#ORDER_ID#' => $orderId,
						'#ORDER_DATE#' => FormatDate($culture->getLongDateFormat(), $order->getDateInsert()->getTimestamp()),
					]
				),
				'SHOW_URL' => Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Order, $orderId),
				'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
					$order->getPrice(),
					$order->getCurrency()
				),
				'SUM_FOR_PAID_FORMAT' => \CCrmCurrency::MoneyToString(
					$order->getPrice() - $order->getSumPaid(),
					$order->getCurrency()
				),
				'IS_PAID' => $order->isPaid(),
			];

			$basePriceOrder = $order->getBasket()->getBasePrice() + $order->getShipmentCollection()->getBasePriceDelivery();
			if (abs($basePriceOrder - $order->getPrice()) > 1e-5)
			{
				$row['ORDER']['BASE_PRICE_FORMAT'] = \CCrmCurrency::MoneyToString(
					$order->getBasket()->getBasePrice() + $order->getShipmentCollection()->getBasePriceDelivery(),
					$order->getCurrency()
				);
			}

			$row['BASKET'] = [
				'BASE_PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
					$order->getBasket()->getBasePrice(),
					$order->getCurrency()
				),
				'PRICE_FORMAT' => \CCrmCurrency::MoneyToString(
					$order->getBasket()->getPrice(),
					$order->getCurrency()
				),
			];

			$row['CHECK'] = Manager::getCheckData($orderId);

			$data['RESULT'][] = $row;
		}

		return $data;
	}
}
