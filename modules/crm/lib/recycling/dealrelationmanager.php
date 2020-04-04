<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;

class DealRelationManager extends BaseRelationManager
{
	/** @var DealRelationManager|null */
	protected static $instance = null;

	/**
	 * @return DealRelationManager
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DealRelationManager();
		}
		return self::$instance;
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	public function buildCollection($entityID, array &$recyclingData)
	{
		$companyID = isset($recyclingData['COMPANY_ID']) ? (int)$recyclingData['COMPANY_ID'] : 0;
		unset($recyclingData['COMPANY_ID']);

		$contactIDs = isset($recyclingData['CONTACT_IDS']) && is_array($recyclingData['CONTACT_IDS'])
			? $recyclingData['CONTACT_IDS'] : array();
		unset($recyclingData['CONTACT_IDS']);

		$parentLeadID = isset($recyclingData['PARENT_LEAD_ID']) ? $recyclingData['PARENT_LEAD_ID'] : 0;
		unset($recyclingData['PARENT_LEAD_ID']);

		$relations = array();
		$this->prepareActivityRelations(
			\CCrmOwnerType::Deal,
			$entityID,
			$recyclingData,
			$relations
		);

		if($companyID > 0)
		{
			$relations[] = new Relation(
				\CCrmOwnerType::Company,
				$companyID,
				\CCrmOwnerType::Deal,
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
					\CCrmOwnerType::Deal,
					$entityID
				);
			}
		}

		if($parentLeadID > 0)
		{
			$relations[] = new Relation(
				\CCrmOwnerType::Lead,
				$parentLeadID,
				\CCrmOwnerType::Deal,
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
			$fields['COMPANY_ID'] = $companyIDs[0];
		}

		$contactIDs = Crm\Entity\Contact::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Contact)
		);
		if(!empty($contactIDs))
		{
			$fields['CONTACT_IDS'] = $contactIDs;
		}

		$parentLeadIDs = Crm\Entity\Lead::selectExisted(
			$map->getSourceEntityIDs(\CCrmOwnerType::Lead)
		);
		if(!empty($parentLeadIDs))
		{
			$fields['LEAD_ID'] = $parentLeadIDs[0];
		}
	}
}