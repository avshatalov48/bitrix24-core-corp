<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

class DealBinder extends BaseBinder
{
	/** @var DealBinder|null  */
	protected static $instance = null;
	/**
	 * @return DealBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DealBinder();
		}
		return self::$instance;
	}

	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=COMPANY_ID' => $associatedEntityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			$results = array();
			while($fields = $dbResult->Fetch())
			{
				$results[] = (int)$fields['ID'];
			}
			return $results;
		}
		elseif($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			return Crm\Binding\DealContactTable::getContactDealIDs($associatedEntityID);
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

		$entity = new \CCrmDeal(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			foreach($entityIDs as $entityID)
			{
				$fields = array('COMPANY_ID' => 0);
				$entity->Update(
					$entityID,
					$fields,
					false,
					false,
					$this->getUnbindUpdateOptions((int)$associatedEntityTypeID, [$associatedEntityID]),
				);
			}
		}
		elseif($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$bindingMap = Crm\Binding\DealContactTable::getBulkDealBindings($entityIDs);
			foreach($bindingMap as $entityID => $bindings)
			{
				Crm\Binding\EntityBinding::removeEntityBinding(\CCrmOwnerType::Contact, $associatedEntityID, $bindings);
				if(!empty($bindings) && !Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
				{
					Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
				}

				$fields = array('CONTACT_BINDINGS' => $bindings);
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

		$entity = new \CCrmDeal(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			foreach($entityIDs as $entityID)
			{
				$fields = array('COMPANY_ID' => $associatedEntityID);
				$entity->Update(
					$entityID,
					$fields,
					true,
					false,
				);
			}
		}
		elseif($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$bindingMap = Crm\Binding\DealContactTable::getBulkDealBindings($entityIDs);
			foreach($bindingMap as $entityID => $bindings)
			{
				Crm\Binding\EntityBinding::addEntityBinding(\CCrmOwnerType::Contact, $associatedEntityID, $bindings);
				if(!Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
				{
					Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
				}

				$fields = array('CONTACT_BINDINGS' => $bindings);
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
	public function unbindEntity($associatedEntityTypeID, array $associatedEntityIDs, $entityID)
	{
		if(empty($associatedEntityIDs))
		{
			return;
		}

		$entity = new \CCrmDeal(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$fields = array('COMPANY_ID' => 0);
			$entity->Update(
				$entityID,
				$fields,
				false,
				false,
				$this->getUnbindUpdateOptions((int)$associatedEntityTypeID, $associatedEntityIDs),
			);
		}
		elseif($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$bindings = Crm\Binding\DealContactTable::getDealBindings($entityID);
			foreach($associatedEntityIDs as $associatedEntityID)
			{
				Crm\Binding\EntityBinding::removeEntityBinding(\CCrmOwnerType::Contact, $associatedEntityID, $bindings);
			}
			if(!Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
			{
				Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
			}

			$fields = array('CONTACT_BINDINGS' => $bindings);
			$entity->Update(
				$entityID,
				$fields,
				false,
				false,
				$this->getUnbindUpdateOptions((int)$associatedEntityTypeID, $associatedEntityIDs),
			);
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

		$entity = new \CCrmDeal(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$fields = array('COMPANY_ID' => $associatedEntityIDs[0]);
			$entity->Update(
				$entityID,
				$fields,
				true,
				false,
			);
		}
		elseif($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$bindings = Crm\Binding\DealContactTable::getDealBindings($entityID);
			foreach($associatedEntityIDs as $associatedEntityID)
			{
				Crm\Binding\EntityBinding::addEntityBinding(\CCrmOwnerType::Contact, $associatedEntityID, $bindings);
			}
			if(!Crm\Binding\EntityBinding::findPrimaryBinding($bindings))
			{
				Crm\Binding\EntityBinding::markFirstAsPrimary($bindings);
			}
			$fields = array('CONTACT_BINDINGS' => $bindings);
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
