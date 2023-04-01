<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

class LeadBinder extends BaseBinder
{
	/** @var LeadBinder|null  */
	protected static $instance = null;
	/**
	 * @return LeadBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadBinder();
		}
		return self::$instance;
	}

	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$results = array();
			$dbResult = Crm\LeadTable::getList(
				array(
					'select' => array('ID'),
					'filter' => array('=COMPANY_ID' => $associatedEntityID)
				)
			);
			while($fields = $dbResult->fetch())
			{
				$results[] = (int)$fields['ID'];
			}
			return $results;
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

		$entity = new \CCrmLead(false);
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
			$bindingMap = Crm\Binding\LeadContactTable::getBulkLeadBindings($entityIDs);
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

		$entity = new \CCrmLead(false);
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
			$bindingMap = Crm\Binding\LeadContactTable::getBulkLeadBindings($entityIDs);
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
	public function bindEntity($associatedEntityTypeID, array $associatedEntityIDs, $entityID)
	{
		if(empty($associatedEntityIDs))
		{
			return;
		}

		$entity = new \CCrmLead(false);
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
			$bindings = Crm\Binding\LeadContactTable::getLeadBindings($entityID);
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
