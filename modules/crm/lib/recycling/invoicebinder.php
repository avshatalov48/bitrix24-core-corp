<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;

class InvoiceBinder extends BaseBinder
{
	/** @var InvoiceBinder|null  */
	protected static $instance = null;
	/**
	 * @return InvoiceBinder|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new InvoiceBinder();
		}
		return self::$instance;
	}

	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'UF_CONTACT_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'UF_COMPANY_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Deal)
		{
			$fieldName = 'UF_DEAL_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}

		$dbResult = \CCrmInvoice::GetList(
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
	public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		if($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'UF_CONTACT_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'UF_COMPANY_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Deal)
		{
			$fieldName = 'UF_DEAL_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}

		$dbResult = \CCrmInvoice::GetList(
			array('ID' => 'ASC'),
			array('@ID' => $entityIDs),
			false,
			false,
			array('ID', $fieldName)
		);

		$entity = new \CCrmInvoice(false);
		while($fields = $dbResult->Fetch())
		{
			if(isset($fields[$fieldName]) && $fields[$fieldName] == $associatedEntityID)
			{
				$entity->Update(
					$fields['ID'],
					array($fieldName => null),
					$this->getUnbindUpdateOptions((int)$associatedEntityTypeID, [$associatedEntityID]),
				);
			}
		}
	}
	public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return;
		}

		if($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'UF_CONTACT_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'UF_COMPANY_ID';
		}
		else if($associatedEntityTypeID === \CCrmOwnerType::Deal)
		{
			$fieldName = 'UF_DEAL_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}

		$dbResult = \CCrmInvoice::GetList(
			array('ID' => 'ASC'),
			array('@ID' => $entityIDs),
			false,
			false,
			array('ID', $fieldName)
		);

		$entity = new \CCrmInvoice(false);
		while($fields = $dbResult->Fetch())
		{
			if(!(isset($fields[$fieldName]) && $fields[$fieldName] > 0))
			{
				$entity->Update(
					$fields['ID'],
					array($fieldName => $associatedEntityID),
				);
			}
		}
	}
}
