<?php

namespace Bitrix\Crm\Service\UserPermissions\Helper;

use Bitrix\Crm\ItemIdentifier;

class Payment
{
	public static function getOrderIdByPaymentId(int $paymentId): int
	{
		if ($paymentId > 0)
		{
			$result = \Bitrix\Crm\Order\Payment::getList(array(
				'filter' => [
					'=ID' => $paymentId,
				],
				'limit' => 1
			));

			$paymentData = $result->fetch();
			$orderId = $paymentData['ORDER_ID'];
			if ($orderId <= 0)
			{
				return 0;
			}

			return $orderId;
		}

		return 0;
	}

	public static function getBoundIdentifierByEntityId(int $id): ?ItemIdentifier
	{
		$orderId = self::getOrderIdByPaymentId($id);

		return $orderId > 0 ? Order::getBoundIdentifierByOrderId($orderId) : null;
	}
}
