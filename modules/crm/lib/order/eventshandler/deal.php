<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Crm;
use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Deal
{
	/**
	 * @param int $id
	 * @return bool
	 */
	public static function onBeforeCrmDealDelete(int $id): bool
	{
		global $APPLICATION;
		$result = true;

		$orderList = self::getEntityOrderList($id);
		foreach ($orderList as $order)
		{
			$deleteResult = Crm\Order\Order::delete($order->getId());
			if (!$deleteResult->isSuccess())
			{
				$result = false;

				\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
				$APPLICATION->ThrowException(
					Loc::getMessage(
						'CRM_DELETE_ERROR',
						['#ERROR#' => implode('<br>', $deleteResult->getErrorMessages())]
					),
					'system'
				);
				break;
			}
		}

		return $result;
	}

	/**
	 * @param int $id
	 * @param array $rows
	 *
	 * @return Result
	 */
	public static function OnBeforeCrmDealProductRowsSave(int $id, array $rows): Result
	{
		return (new OrderDealSynchronizer)->verifyDealProducts($id, $rows);
	}

	/**
	 * @param int $id
	 *
	 * @return void
	 */
	public static function OnAfterCrmDealProductRowsSave(int $id)
	{
		(new OrderDealSynchronizer)->updateOrderFromDeal($id);
	}

	/**
	 * @return Crm\Order\Order[]
	 */
	private static function getEntityOrderList(int $dealId): array
	{
		$orderList = [];

		$bindingResult = Crm\Order\EntityBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=OWNER_ID' => $dealId,
				'=OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
			],
			'order' => ['ORDER_ID' => 'ASC'],
		]);
		while ($bindingData = $bindingResult->fetch())
		{
			$order = Crm\Order\Order::load($bindingData['ORDER_ID']);
			if ($order)
			{
				$orderList[] = $order;
			}
		}

		return $orderList;
	}
}
