<?

namespace Bitrix\Crm\Order\Permissions;

/**
 * Class Payment
 * @package Bitrix\Crm\Order\Permissions
 */
class Payment
{
	/**
	 * @param $id
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkUpdatePermission($id = 0, $userPermissions = null)
	{
		$id = (int)$id;
		$orderId = 0;

		if ($id > 0)
		{
			$result = \Bitrix\Crm\Order\Payment::getList(array(
				'filter' => [
					'=ID' => $id,
				],
				'limit' => 1
			));

			$paymentData = $result->fetch();
			$orderId = $paymentData['ORDER_ID'];
			if ($orderId <= 0)
			{
				return false;
			}
		}

		return Order::checkUpdatePermission($orderId, $userPermissions);
	}

	/**
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkCreatePermission($userPermissions = null)
	{
		return Order::checkCreatePermission($userPermissions);
	}

	/**
	 * @param $id
	 * @param null $userPermissions
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function checkDeletePermission($id, $userPermissions = null)
	{
		return self::checkUpdatePermission($id, $userPermissions);
	}

	/**
	 * @param int $id
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkReadPermission($id = 0, $userPermissions = null)
	{
		$id = (int)$id;
		$orderId = 0;

		if ($id > 0)
		{
			$result = \Bitrix\Crm\Order\Payment::getList(array(
				'filter' => [
					'=ID' => $id,
				],
				'limit' => 1
			));

			$paymentData = $result->fetch();
			$orderId = (int)$paymentData['ORDER_ID'];
			if ($orderId <= 0)
			{
				return false;
			}
		}

		return Order::checkReadPermission($orderId, $userPermissions);
	}
}
