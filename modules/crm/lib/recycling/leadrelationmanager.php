<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;

class LeadRelationManager extends BaseRelationManager
{
	/** @var LeadRelationManager|null */
	protected static $instance = null;

	/**
	 * @return LeadRelationManager
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadRelationManager();
		}
		return self::$instance;
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	public function buildCollection($entityID, array &$recyclingData)
	{
		$companyID = isset($recyclingData['COMPANY_ID']) ? (int)$recyclingData['COMPANY_ID'] : 0;
		unset($recyclingData['COMPANY_ID']);

		$contactIDs = isset($recyclingData['CONTACT_IDS']) && is_array($recyclingData['CONTACT_IDS'])
			? $recyclingData['CONTACT_IDS'] : [];
		unset($recyclingData['CONTACT_IDS']);

		$childContactIDs = isset($recyclingData['CHILD_CONTACT_IDS']) && is_array($recyclingData['CHILD_CONTACT_IDS'])
			? $recyclingData['CHILD_CONTACT_IDS'] : [];
		unset($recyclingData['CHILD_CONTACT_IDS']);

		$childCompanyIDs = isset($recyclingData['CHILD_COMPANY_IDS']) && is_array($recyclingData['CHILD_COMPANY_IDS'])
			? $recyclingData['CHILD_COMPANY_IDS'] : [];
		unset($recyclingData['CHILD_COMPANY_IDS']);

		$childDealIDs = isset($recyclingData['CHILD_DEAL_IDS']) && is_array($recyclingData['CHILD_DEAL_IDS'])
			? $recyclingData['CHILD_DEAL_IDS'] : [];
		unset($recyclingData['CHILD_DEAL_IDS']);

		$childQuoteIds = isset($recyclingData['CHILD_QUOTE_IDS']) && is_array($recyclingData['CHILD_QUOTE_IDS'])
			? $recyclingData['CHILD_QUOTE_IDS'] : [];
		unset($recyclingData['CHILD_QUOTE_IDS']);

		$relations = [];
		$this->prepareActivityRelations(
			\CCrmOwnerType::Lead,
			$entityID,
			$recyclingData,
			$relations
		);

		if($companyID > 0)
		{
			$relations[] = new Relation(
				\CCrmOwnerType::Company,
				$companyID,
				\CCrmOwnerType::Lead,
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
					\CCrmOwnerType::Lead,
					$entityID
				);
			}
		}

		if(!empty($childContactIDs))
		{
			foreach($childContactIDs as $contactID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Lead,
					$entityID,
					\CCrmOwnerType::Contact,
					$contactID
				);
			}
		}

		if(!empty($childCompanyIDs))
		{
			foreach($childCompanyIDs as $companyID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Lead,
					$entityID,
					\CCrmOwnerType::Company,
					$companyID
				);
			}
		}

		if(!empty($childDealIDs))
		{
			foreach($childDealIDs as $dealID)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Lead,
					$entityID,
					\CCrmOwnerType::Deal,
					$dealID
				);
			}
		}

		if (!empty($childQuoteIds))
		{
			foreach ($childQuoteIds as $quoteId)
			{
				$relations[] = new Relation(
					\CCrmOwnerType::Lead,
					$entityID,
					\CCrmOwnerType::Quote,
					$quoteId
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
			Crm\Entity\Lead::setChildEntityIDs($entityID, \CCrmOwnerType::Contact, $contactIDs);
		}

		$companyIDs = Crm\Entity\Company::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Company)
		);
		if(!empty($companyIDs))
		{
			Crm\Entity\Lead::setChildEntityIDs($entityID, \CCrmOwnerType::Company, $companyIDs);
		}

		$dealIDs = Crm\Entity\Deal::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Deal)
		);
		if(!empty($dealIDs))
		{
			Crm\Entity\Lead::setChildEntityIDs($entityID, \CCrmOwnerType::Deal, $dealIDs);
		}

		$quoteIds = Crm\Entity\Quote::selectExisted(
			$map->getDestinationEntityIDs(\CCrmOwnerType::Quote)
		);
		if(!empty($quoteIds))
		{
			Crm\Entity\Lead::setChildEntityIDs($entityID, \CCrmOwnerType::Quote, $quoteIds);
		}
	}
}