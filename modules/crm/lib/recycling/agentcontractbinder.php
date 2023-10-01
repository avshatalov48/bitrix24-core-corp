<?php

namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

/**
 * Class AgentContractBinder
 *
 * @package Bitrix\Crm\Recycling
 */
class AgentContractBinder extends BaseBinder
{
	/** @var AgentContractBinder|null  */
	protected static $instance = null;
	/**
	 * @return AgentContractBinder|null
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new AgentContractBinder();
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

			$bindings = Crm\Integration\Catalog\Contractor\AgentContractContractorTable::query()
				->where('ENTITY_TYPE_ID', $associatedEntityTypeID,)
				->where('ENTITY_ID', $associatedEntityID,)
				->setSelect(['CONTRACT_ID'])
				->exec()
			;
			while ($binding = $bindings->fetch())
			{
				$result[] = $binding['CONTRACT_ID'];
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
			(new Crm\Integration\Catalog\Contractor\AgentContractContactCompanyBinding($associatedEntityTypeID))
				->bindToDocuments($associatedEntityID, $entityIDs);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
			throw new Main\NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}
}
