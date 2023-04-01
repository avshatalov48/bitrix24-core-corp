<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

class QuoteBinder extends BaseBinder
{
	/** @var QuoteBinder|null  */
	protected static $instance = null;
	/**
	 * @return QuoteBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new QuoteBinder();
		}
		return self::$instance;
	}

	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Company
			|| $associatedEntityTypeID === \CCrmOwnerType::Deal
		)
		{
			$fieldName = $associatedEntityTypeID === \CCrmOwnerType::Company ? 'COMPANY_ID' : 'DEAL_ID';
			$dbResult = \CCrmQuote::GetList(
				array(),
				array("={$fieldName}" => $associatedEntityID, 'CHECK_PERMISSIONS' => 'N'),
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
			return Crm\Binding\QuoteContactTable::getContactQuotesIDs($associatedEntityID);
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

		$entity = new \CCrmQuote(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company
			|| $associatedEntityTypeID === \CCrmOwnerType::Deal
		)
		{
			$fieldName = $associatedEntityTypeID === \CCrmOwnerType::Company ? 'COMPANY_ID' : 'DEAL_ID';
			foreach($entityIDs as $entityID)
			{
				$fields = array($fieldName => 0);
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
			$bindingMap = Crm\Binding\QuoteContactTable::getBulkQuoteBindings($entityIDs);
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

		$entity = new \CCrmQuote(false);
		if($associatedEntityTypeID === \CCrmOwnerType::Company
			|| $associatedEntityTypeID === \CCrmOwnerType::Deal
		)
		{
			$fieldName = $associatedEntityTypeID === \CCrmOwnerType::Company ? 'COMPANY_ID' : 'DEAL_ID';
			foreach($entityIDs as $entityID)
			{
				$fields = array($fieldName => $associatedEntityID);
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
			$bindingMap = Crm\Binding\QuoteContactTable::getBulkQuoteBindings($entityIDs);
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
}
