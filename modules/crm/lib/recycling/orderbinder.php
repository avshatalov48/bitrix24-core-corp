<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Main;

class OrderBinder extends BaseBinder
{
	/** @var OrderBinder|null  */
	protected static $instance = null;
	/**
	 * @return OrderBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderBinder();
		}
		return self::$instance;
	}

	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Company
			|| $associatedEntityTypeID === \CCrmOwnerType::Contact
		)
		{
			$result = [];
			$bindings = OrderContactCompanyTable::query()
				->where('ENTITY_TYPE_ID', $associatedEntityTypeID,)
				->where('ENTITY_ID', $associatedEntityID,)
				->setSelect(['ORDER_ID'])
				->exec()
			;
			while ($binding = $bindings->fetch())
			{
				$result[] = $binding['ORDER_ID'];
			}

			return $result;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}

	public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		$saleModuleInstalled = Main\Loader::includeModule('sale');
		if(in_array($associatedEntityTypeID, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			if ($saleModuleInstalled)
			{
				(new \Bitrix\Crm\Order\ContactCompanyBinding($associatedEntityTypeID))
					->unbindFromOrders($associatedEntityID, $entityIDs)
				;
			}
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
	public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		$saleModuleInstalled = Main\Loader::includeModule('sale');
		if(in_array($associatedEntityTypeID, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			if ($saleModuleInstalled)
			{
				(new \Bitrix\Crm\Order\ContactCompanyBinding($associatedEntityTypeID))
					->bindToOrders($associatedEntityID, $entityIDs)
				;
			}
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
}
