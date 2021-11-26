<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;

class CompanyRelationManager extends BaseRelationManager
{
	/** @var CompanyRelationManager|null */
	protected static $instance = null;

	/**
	 * @return CompanyRelationManager
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyRelationManager();
		}
		return self::$instance;
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	public function buildCollection($entityID, array &$recyclingData)
	{
		$contactIDs = isset($recyclingData['CONTACT_IDS']) && is_array($recyclingData['CONTACT_IDS']) ? $recyclingData['CONTACT_IDS'] : array();
		unset($recyclingData['CONTACT_IDS']);

		$dealIDs = isset($recyclingData['DEAL_IDS']) && is_array($recyclingData['DEAL_IDS']) ? $recyclingData['DEAL_IDS'] : array();
		unset($recyclingData['DEAL_IDS']);

		$leadIDs = isset($recyclingData['LEAD_IDS']) && is_array($recyclingData['LEAD_IDS']) ? $recyclingData['LEAD_IDS'] : array();
		unset($recyclingData['LEAD_IDS']);

		$parentLeadID = isset($recyclingData['PARENT_LEAD_ID']) ? $recyclingData['PARENT_LEAD_ID'] : 0;
		unset($recyclingData['PARENT_LEAD_ID']);

		$relations = [];

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Company)
			->buildCollection($relations, $recyclingData);

		$this->prepareActivityRelations(
			\CCrmOwnerType::Company,
			$entityID,
			$recyclingData,
			$relations
		);

		if(!empty($contactIDs))
		{
			foreach($contactIDs as $contactID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmOwnerType::Contact,
					$contactID
				);
			}
		}

		if(!empty($dealIDs))
		{
			foreach($dealIDs as $dealID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmOwnerType::Deal,
					$dealID
				);
			}
		}

		if(!empty($leadIDs))
		{
			foreach($leadIDs as $leadID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmOwnerType::Lead,
					$leadID
				);
			}
		}

		if($parentLeadID > 0)
		{
			$relations[] = new Relation(
				\CCrmOwnerType::Lead,
				$parentLeadID,
				\CCrmOwnerType::Company,
				$entityID
			);
		}

		return $relations;
	}
	public function prepareRecoveryFields(array &$fields, RelationMap $map)
	{
		if(!$map->isBuilt())
		{
			$map->build();
		}

		$parentLeadIDs = Crm\Entity\Lead::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Lead)
		);
		if(!empty($parentLeadIDs))
		{
			$fields['LEAD_ID'] = $parentLeadIDs[0];
		}
	}
	public function recoverBindings($entityID, RelationMap $map)
	{
		if(!$map->isBuilt())
		{
			$map->build();
		}

		$contactIDs = Crm\Entity\Contact::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Contact)
		);
		if(!empty($contactIDs))
		{
			ContactBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$entityID,
				$contactIDs
			);
		}

		$dealIDs = Crm\Entity\Deal::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Deal)
		);
		if(!empty($dealIDs))
		{
			DealBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$entityID,
				$dealIDs
			);
		}

		$leadIDs = Crm\Entity\Lead::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Lead)
		);
		if(!empty($leadIDs))
		{
			LeadBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$entityID,
				$leadIDs
			);
		}

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Company)
			->recoverBindings($map);
	}
}
