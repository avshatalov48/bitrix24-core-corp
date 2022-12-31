<?php

namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

/**
 * Class StoreDocumentBinder
 *
 * @package Bitrix\Crm\Recycling
 */
class StoreDocumentBinder extends BaseBinder
{
	/** @var StoreDocumentBinder|null  */
	protected static $instance = null;
	/**
	 * @return StoreDocumentBinder|null
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new StoreDocumentBinder();
		}
		return self::$instance;
	}

	/**
	 * @inheritDoc
	 */
	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID)
	{
		if (
			$associatedEntityTypeID === \CCrmOwnerType::Company
			|| $associatedEntityTypeID === \CCrmOwnerType::Contact
		)
		{
			$result = [];

			$bindings = Crm\Integration\Catalog\Contractor\StoreDocumentContractorTable::query()
				->where('ENTITY_TYPE_ID', $associatedEntityTypeID,)
				->where('ENTITY_ID', $associatedEntityID,)
				->setSelect(['DOCUMENT_ID'])
				->exec()
			;
			while ($binding = $bindings->fetch())
			{
				$result[] = $binding['DOCUMENT_ID'];
			}

			return $result;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}

	/**
	 * @inheritDoc
	 */
	public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		throw new Main\NotSupportedException("Not supported in current context.");
	}

	/**
	 * @inheritDoc
	 */
	public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		if (in_array($associatedEntityTypeID, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			(new Crm\Integration\Catalog\Contractor\ContactCompanyBinding($associatedEntityTypeID))
				->bindToDocuments($associatedEntityID, $entityIDs);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
}
