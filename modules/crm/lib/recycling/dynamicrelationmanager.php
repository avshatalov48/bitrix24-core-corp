<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main\DI\ServiceLocator;

class DynamicRelationManager extends BaseRelationManager
{
	/** @var DynamicRelationManager|null */
	protected static $instance = null;

	protected $entityTypeId;

	/**
	 * @param int $entityTypeId
	 * @return DynamicRelationManager
	 */
	public static function getInstance(int $entityTypeId): DynamicRelationManager
	{
		if(self::$instance === null)
		{
			self::$instance = ServiceLocator::getInstance()->get('crm.recycling.dynamicRelationManager');
			self::$instance->setEntityTypeID($entityTypeId);
		}
		return self::$instance;
	}

	/**
	 * @return int
	 */
	public function setEntityTypeID(int $entityTypeId): void
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * @return int
	 */
	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}

	public function buildCollection($entityID, array &$recyclingData): array
	{
		$companyID = (isset($recyclingData['COMPANY_ID']) ? (int)$recyclingData['COMPANY_ID'] : 0);
		unset($recyclingData['COMPANY_ID']);

		$contactIDs = (isset($recyclingData['CONTACT_IDS']) && is_array($recyclingData['CONTACT_IDS'])
			? $recyclingData['CONTACT_IDS'] : []);
		unset($recyclingData['CONTACT_IDS']);

		$relations = [];
		$this->prepareActivityRelations(
			$this->getEntityTypeID(),
			$entityID,
			$recyclingData,
			$relations
		);

		if($companyID > 0)
		{
			$relations[] = new Relation(
				\CCrmOwnerType::Company,
				$companyID,
				$this->getEntityTypeID(),
				$entityID
			);
		}

		if(!empty($contactIDs))
		{
			foreach($contactIDs as $contactID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Contact,
					$contactID,
					$this->getEntityTypeID(),
					$entityID
				);
			}
		}

		return $relations;
	}

	public function prepareRecoveryFields(array &$fields, RelationMap $map)
	{
		if(!$map->isBuilt())
		{
			$map->build();
		}

		$companyIDs = Crm\Entity\Company::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Company)
		);
		if(!empty($companyIDs))
		{
			$fields['COMPANY_ID'] = $companyIDs[0];
		}

		$contactIDs = Crm\Entity\Contact::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Contact)
		);
		if(!empty($contactIDs))
		{
			$fields['CONTACT_IDS'] = $contactIDs;
		}
	}
}