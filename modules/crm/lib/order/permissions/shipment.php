<?

namespace Bitrix\Crm\Order\Permissions;

/**
 * Class Shipment
 * @package Bitrix\Crm\Order\Permissions
 */
class Shipment
{
	protected static $TYPE_NAME = 'ORDER_SHIPMENT';
	protected static $ORDER_TYPE_NAME = 'ORDER';

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
			$result = \Bitrix\Crm\Order\Shipment::getList(array(
				'filter' => [
					'=ID' => $id,
				],
				'limit' => 1
			));

			$shipmentData = $result->fetch();
			$orderId = $shipmentData['ORDER_ID'];
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
			$result = \Bitrix\Crm\Order\Shipment::getList(array(
				'filter' => [
					'=ID' => $id,
				],
				'limit' => 1
			));

			$shipmentData = $result->fetch();
			$orderId = $shipmentData['ORDER_ID'];
			if ($orderId <= 0)
			{
				return false;
			}
		}

		return Order::checkReadPermission($orderId, $userPermissions);
	}

	/**
	 * @param $statusId
	 * @param $permissionTypeId
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkStatusPermission($statusId, $permissionTypeId, \CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		$permissionName = \Bitrix\Crm\Security\EntityPermissionType::resolveName($permissionTypeId);
		$entityAttrs = array("STATUS_ID{$statusId}");

		return $permissionName !== '' &&
			($userPermissions->GetPermType(self::$TYPE_NAME, $permissionName, $entityAttrs) > BX_CRM_PERM_NONE);
	}
}
