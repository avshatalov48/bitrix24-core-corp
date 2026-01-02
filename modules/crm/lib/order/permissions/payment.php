<?

namespace Bitrix\Crm\Order\Permissions;

use Bitrix\Crm\Service\Container;

/**
 * @deprecated
 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()
 */
class Payment
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
				->canUpdate(\CCrmOwnerType::OrderPayment, $id)
			;
		}

		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canUpdateItems(\CCrmOwnerType::OrderPayment)
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
			->canAddItems(\CCrmOwnerType::OrderPayment)
		;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
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
				->canRead(\CCrmOwnerType::OrderPayment, $id)
			;
		}

		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canReadItems(\CCrmOwnerType::OrderPayment)
		;
	}
}
