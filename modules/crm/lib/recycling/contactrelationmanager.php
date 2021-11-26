<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;

class ContactRelationManager extends BaseRelationManager
{
	/** @var ContactRelationManager|null */
	protected static $instance = null;

	/**
	 * @return ContactRelationManager
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactRelationManager();
		}
		return self::$instance;
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Contact;
	}

	public function buildCollection($entityID, array &$recyclingData)
	{
		$companyIDs = isset($recyclingData['COMPANY_IDS']) && is_array($recyclingData['COMPANY_IDS'])
			? $recyclingData['COMPANY_IDS'] : array();
		unset($recyclingData['COMPANY_IDS']);

		$dealIDs = isset($recyclingData['DEAL_IDS']) && is_array($recyclingData['DEAL_IDS'])
			? $recyclingData['DEAL_IDS'] : array();
		unset($recyclingData['DEAL_IDS']);

		$leadIDs = isset($recyclingData['LEAD_IDS']) && is_array($recyclingData['LEAD_IDS'])
			? $recyclingData['LEAD_IDS'] : array();
		unset($recyclingData['LEAD_IDS']);

		$parentLeadID = isset($recyclingData['PARENT_LEAD_ID']) ? $recyclingData['PARENT_LEAD_ID'] : 0;
		unset($recyclingData['PARENT_LEAD_ID']);

		$relations = [];

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Contact)
			->buildCollection($relations, $recyclingData);

		$this->prepareActivityRelations(
			\CCrmOwnerType::Contact,
			$entityID,
			$recyclingData,
			$relations
		);

		if(!empty($companyIDs))
		{
			foreach($companyIDs as $companyID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Company,
					$companyID,
					\CCrmOwnerType::Contact,
					$entityID
				);
			}
		}

		if(!empty($dealIDs))
		{
			foreach($dealIDs as $dealID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Contact,
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
					\CCrmOwnerType::Contact,
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
				\CCrmOwnerType::Contact,
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

		$companyIDs = Crm\Entity\Company::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Company)
		);
		if(!empty($companyIDs))
		{
			$fields['COMPANY_IDS'] = $companyIDs;
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

		$dealIDs = Crm\Entity\Deal::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Deal)
		);
		if(!empty($dealIDs))
		{
			DealBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
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
				\CCrmOwnerType::Contact,
				$entityID,
				$leadIDs
			);
		}

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Contact)
			->recoverBindings($map);
	}
}
