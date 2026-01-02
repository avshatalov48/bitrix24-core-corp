<?

namespace Bitrix\Crm\Order\Permissions;

use Bitrix\Crm\Service\Container;

/**
 * @deprecated
 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()
 */
class Shipment
{
	/**
	 * @param $id
	 * @return bool
	 */
	public static function checkUpdatePermission($id = 0)
	{
		$id = (int)$id;

		if ($id > 0)
		{
			return Container::getInstance()
				->getUserPermissions()
				->item()
				->canUpdate(\CCrmOwnerType::OrderShipment, $id)
			;
		}

		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canUpdateItems(\CCrmOwnerType::OrderShipment)
		;
	}

	/**
	 * @return bool
	 */
	public static function checkCreatePermission()
	{
		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canAddItems(\CCrmOwnerType::Order)
		;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function checkDeletePermission($id)
	{
		return self::checkUpdatePermission($id);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public static function checkReadPermission($id = 0)
	{
		$id = (int)$id;

		if ($id > 0)
		{
			return Container::getInstance()
				->getUserPermissions()
				->item()
				->canRead(\CCrmOwnerType::OrderShipment, $id)
			;
		}

		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canReadItems(\CCrmOwnerType::OrderShipment)
		;
	}
}
