<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Order;
use Bitrix\Crm\Timeline\ProductCompilationController;
use Bitrix\Main;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;

class CompilationManager
{
	private static $currentCompilationDealId;
	private static $currentCompilationChatId;

	private static function getCurrentCompilationDealId(): ?int
	{
		if (self::$currentCompilationDealId)
		{
			return self::$currentCompilationDealId;
		}

		$session = Main\Application::getInstance()->getSession();
		if ($session->has('CATALOG_CURRENT_COMPILATION_DATA'))
		{
			self::$currentCompilationDealId = $session->get('CATALOG_CURRENT_COMPILATION_DATA')['DEAL_ID'];
			self::$currentCompilationChatId = $session->get('CATALOG_CURRENT_COMPILATION_DATA')['CHAT_ID'];
			$session->remove('CATALOG_CURRENT_COMPILATION_DATA');
		}
		else
		{
			self::$currentCompilationDealId = null;
		}

		return self::$currentCompilationDealId;
	}

	/**
	 * Binded order for comlitation deal.
	 *
	 * @param mixed $order
	 *
	 * @return int|null complitation deal id
	 */
	public static function processOrderForCompilation($order): ?int
	{
		if (!($order instanceof \Bitrix\Crm\Order\Order))
		{
			return null;
		}

		$currentCompilationDealId = self::getCurrentCompilationDealId();
		if ($currentCompilationDealId)
		{
			$dealOrders = OrderEntityTable::getOrderIdsByOwner($currentCompilationDealId, \CCrmOwnerType::Deal);
			if (empty($dealOrders))
			{
				// bind order to deal
				$dealBinding = $order->createEntityBinding();
				$dealBinding->setField('OWNER_TYPE_ID', \CCrmOwnerType::Deal);
				$dealBinding->setField('OWNER_ID', $currentCompilationDealId);
			}
		}

		return $currentCompilationDealId;
	}

	public static function sendOrderBoundEvent($order)
	{
		if (!($order instanceof \Bitrix\Crm\Order\Order))
		{
			return;
		}
		$currentCompilationDealId = self::getCurrentCompilationDealId();
		if (!$currentCompilationDealId)
		{
			return;
		}

		if (Main\Loader::includeModule('pull'))
		{
			$orderId = $order->getId();
			$entityBinding = $order->getEntityBinding();
			$dealId = $entityBinding ? $entityBinding->getOwnerId() : null;

			if (!$dealId)
			{
				return;
			}

			$productList = self::getProductListForOrderBoundEvent($dealId);

			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER',
				[
					'module_id' => 'crm',
					'command' => 'onOrderBound',
					'params' => [
						'FIELDS' => [
							'ID' => $orderId,
							'PRODUCT_LIST' => $productList,
						]
					]
				]
			);
		}
	}

	private static function getProductListForOrderBoundEvent($dealId)
	{
		$productList = [];

		$products = \CCrmProductRow::LoadRows('D', $dealId);
		$vatList = \CCrmTax::GetVatRateInfos();
		foreach ($products as $product)
		{
			$item = [
				'id' => $product['PRODUCT_ID'],
				'name' => $product['PRODUCT_NAME'],
				'price' => $product['PRICE'],
				'quantity' => $product['QUANTITY'],
				'measureName' => $product['MEASURE_NAME'],
				'measureCode' => $product['MEASURE_CODE'],
				'customized' => $product['CUSTOMIZED']
			];

			if ($product['DISCOUNT_RATE'])
			{
				$item['discount'] = [
					'discountType' => $product['DISCOUNT_TYPE'],
					'discountRate' => $product['DISCOUNT_RATE'],
					'discountSum' => $product['DISCOUNT_SUM'],
				];
			}

			if ($product['TAX_RATE'])
			{
				$taxId = 0;
				foreach ($vatList as $vat)
				{
					if ((int)$vat['VALUE'] === (int)$product['TAX_RATE'])
					{
						$taxId = $vat['ID'];
					}
				}
				$item['tax'] = [
					'id' => $taxId,
					'included' => $product['TAX_INCLUDED'] === 'Y',
				];
			}

			$productList[] = $item;
		}

		return $productList;
	}

	public static function sendToCompilationDealTimeline(\Bitrix\Sale\Order $order): void
	{
		$currentCompilationDealId = self::getCurrentCompilationDealId();

		if (!$currentCompilationDealId)
		{
			return;
		}

		$entityBinding = $order->getEntityBinding();
		if (!$entityBinding)
		{
			return;
		}

		$boundToOrderDealId = $entityBinding->getOwnerId();
		if (!$boundToOrderDealId)
		{
			return;
		}

		if ($boundToOrderDealId !== $currentCompilationDealId)
		{
			$timelineParams = [
				'SETTINGS' => [
					'DEAL_ID' => $currentCompilationDealId,
					'NEW_DEAL_ID' => $boundToOrderDealId,
				]
			];
			ProductCompilationController::getInstance()->onOrderCheckout(
				$currentCompilationDealId,
				$timelineParams
			);

			if (self::$currentCompilationChatId)
			{
				ImOpenLinesManager::getInstance()->sendNewOrderNotification(
					'chat' . self::$currentCompilationChatId,
					$order
				);
			}
		}
	}
}
