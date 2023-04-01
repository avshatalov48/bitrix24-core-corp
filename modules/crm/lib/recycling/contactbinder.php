<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

class ContactBinder extends BaseBinder
{
	/** @var ContactBinder|null  */
	protected static $instance = null;
	/**
	 * @return ContactBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactBinder();
		}
		return self::$instance;
	}
	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			return Crm\Binding\ContactCompanyTable::getCompanyContactIDs($associatedEntityID);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}
	public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return;
		}

		$entity = new \CCrmContact(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$bindingMap = Crm\Binding\ContactCompanyTable::getBulkContactBindings($entityIDs);
			foreach($bindingMap as $entityID => $bindings)
			{
				Crm\Binding\EntityBinding::removeEntityBinding(\CCrmOwnerType::Company, $associatedEntityID, $bindings);
				if(!empty($bindings) && !Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
				{
					Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
				}

				$fields = array('COMPANY_BINDINGS' => $bindings);
				$entity->Update(
					$entityID,
					$fields,
					false,
					false,
					$this->getUnbindUpdateOptions((int)$associatedEntityTypeID, [$associatedEntityID]),
				);
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
		if(empty($entityIDs))
		{
			return;
		}

		$entity = new \CCrmContact(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$bindingMap = Crm\Binding\ContactCompanyTable::getBulkContactBindings($entityIDs);
			foreach($bindingMap as $entityID => $bindings)
			{
				Crm\Binding\EntityBinding::addEntityBinding(\CCrmOwnerType::Company, $associatedEntityID, $bindings);
				if(!Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
				{
					Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
				}

				$fields = array('COMPANY_BINDINGS' => $bindings);
				$entity->Update(
					$entityID,
					$fields,
					true,
					false,
				);
			}
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
	public function bindEntity($associatedEntityTypeID, array $associatedEntityIDs, $entityID)
	{
		if(empty($associatedEntityIDs))
		{
			return;
		}

		$entity = new \CCrmContact(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$bindings = Crm\Binding\ContactCompanyTable::getContactBindings($entityID);
			foreach($associatedEntityIDs as $associatedEntityID)
			{
				Crm\Binding\EntityBinding::addEntityBinding(\CCrmOwnerType::Company, $associatedEntityID, $bindings);
			}
			if(!Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
			{
				Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
			}
			$fields = array('COMPANY_BINDINGS' => $bindings);
			$entity->Update(
				$entityID,
				$fields,
				true,
				false,
			);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
}
