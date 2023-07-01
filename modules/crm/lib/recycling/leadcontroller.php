<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class LeadController extends BaseController
{
	use MultiFieldControllerMixin {
		recoverMultiFields as innerRecoverMultiFields;
	}

	use ActivityControllerMixin;
	use AddressControllerMixin;
	use ProductRowControllerMixin;
	use ObserverControllerMixin;
	use ChatControllerMixin;
	use WaitingControllerMixin;

	/** @var LeadController|null */
	protected static $instance = null;
	/**
	 * @return LeadController|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadController();
		}
		return self::$instance;
	}

	public static function getFieldNames()
	{
		return array(
			'ID',
			'DATE_CREATE', 'DATE_MODIFY', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'ASSIGNED_BY_ID', 'TITLE', 'STATUS_ID', 'STATUS_DESCRIPTION',
			'SOURCE_ID', 'SOURCE_DESCRIPTION', 'CURRENCY_ID', 'OPPORTUNITY',
			'HONORIFIC', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'BIRTHDATE', 'POST', 'COMMENTS',
			'COMPANY_TITLE', 'COMPANY_ID', 'CONTACT_ID', 'OPENED', 'DATE_CLOSED',
			'WEBFORM_ID', 'FACE_ID', 'IS_RETURN_CUSTOMER', 'ORIGINATOR_ID', 'ORIGIN_ID',
			'MOVED_BY_ID', 'MOVED_TIME',
		);
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	public function getSuspendedEntityTypeID()
	{
		return \CCrmOwnerType::SuspendedLead;
	}

	/**
	 * Get recyclebin entity type name.
	 * @see \Bitrix\Crm\Integration\Recyclebin\Lead::getEntityName
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		return 'crm_lead';
	}

	//region ProductRowController
	/**
	 * Get Product Row Owner Type
	 * @return string
	 */
	public function getProductRowOwnerType()
	{
		return \CCrmOwnerTypeAbbr::Lead;
	}

	/**
	 * Get Product Row Suspended Owner Type
	 * @return string
	 */
	public function getProductRowSuspendedOwnerType()
	{
		return \CCrmOwnerTypeAbbr::SuspendedLead;
	}
	//endregion

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		$entityTitle = Crm\Integration\Recyclebin\RecyclingManager::resolveEntityTitle(
			\CCrmOwnerType::Lead,
			$entityID
		);

		return Main\Localization\Loc::getMessage(
			'CRM_LEAD_CTRL_ACTIVITY_OWNER_NOT_FOUND',
			[
				'#OWNER_TITLE#' => $entityTitle,
				'#OWNER_ID#' => $entityID,
				'#ID#' => isset($params['ID']) ? $params['ID'] : '',
				'#TITLE#' => isset($params['title']) ? $params['title'] : ''
			]
		);
	}

	public function getEntityFields($entityID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*')
		);
		$fields = $dbResult->Fetch();
		return is_array($fields) ? $fields : null;
	}

	public function prepareEntityData($entityID, array $params = array())
	{
		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(empty($fields))
		{
			$fields = $this->getEntityFields($entityID);
		}

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityID}.");
		}

		$slots = [
			'FIELDS' => Crm\Entity\FieldContentType::enrichRecycleBinFields(
				new Crm\ItemIdentifier($this->getEntityTypeID(), $entityID),
				array_intersect_key($fields, array_flip(self::getFieldNames())),
			),
		];

		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$slots['COMPANY_ID'] = $companyID;
		}

		$contactIDs = Crm\Binding\LeadContactTable::getLeadContactIDs($entityID);
		if(!empty($contactIDs))
		{
			$slots['CONTACT_IDS'] = $contactIDs;
		}

		$childContactIDs = Crm\Entity\Lead::getChildEntityIDs($entityID, \CCrmOwnerType::Contact);
		if(!empty($childContactIDs))
		{
			$slots['CHILD_CONTACT_IDS'] = $childContactIDs;
		}

		$childCompanyIDs = Crm\Entity\Lead::getChildEntityIDs($entityID, \CCrmOwnerType::Company);
		if(!empty($childCompanyIDs))
		{
			$slots['CHILD_COMPANY_IDS'] = $childCompanyIDs;
		}

		$childDealIDs = Crm\Entity\Lead::getChildEntityIDs($entityID, \CCrmOwnerType::Deal);
		if(!empty($childDealIDs))
		{
			$slots['CHILD_DEAL_IDS'] = $childDealIDs;
		}

		$childQuoteIds = Crm\Entity\Lead::getChildEntityIDs($entityID, \CCrmOwnerType::Quote);
		if(!empty($childQuoteIds))
		{
			$slots['CHILD_QUOTE_IDS'] = $childQuoteIds;
		}

		$slots = array_merge($slots, $this->prepareActivityData($entityID, $params));

		return array(
			'TITLE' => \CCrmOwnerType::GetCaption(
				\CCrmOwnerType::Lead,
				$entityID,
				false,
				array('FIELDS' => $fields)
			),
			'SLOTS' => $slots
		);
	}

	/**
	 * Move entity to Recycle Bin.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional operation parameters.
	 * @return void
	 * @throws Crm\Synchronization\UserFieldSynchronizationException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	public function moveToBin($entityID, array $params = array())
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(empty($fields))
		{
			$fields = $params['FIELDS'] = $this->getEntityFields($entityID);
		}

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityID}.");
		}

		$entityData = $this->prepareEntityData($entityID, $params);

		$recyclingEntity = Crm\Integration\Recyclebin\Lead::createRecycleBinEntity($entityID);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : array();
		$relations = LeadRelationManager::getInstance()->buildCollection($entityID, $slots);
		foreach($slots as $slotKey => $slotData)
		{
			$recyclingEntity->add($slotKey, $slotData);
		}

		$result = $recyclingEntity->save();
		$errors = $result->getErrors();
		if(!empty($errors))
		{
			throw new Main\SystemException($errors[0]->getMessage(), $errors[0]->getCode());
		}

		$recyclingEntityID = $recyclingEntity->getId();

		//region Convert User Fields to Suspended Type
		$suspendedUserFields = $this->prepareSuspendedUserFields($entityID);
		if(!empty($suspendedUserFields))
		{
			$this->saveSuspendedUserFields($recyclingEntityID, $suspendedUserFields);
		}
		//endregion

		$this->suspendActivities($entityData, $entityID, $recyclingEntityID);
		$this->suspendMultiFields($entityID, $recyclingEntityID);
		$this->suspendAddresses($entityID, $recyclingEntityID);
		$this->suspendTimeline($entityID, $recyclingEntityID);
		$this->suspendDocuments($entityID, $recyclingEntityID);
		$this->suspendLiveFeed($entityID, $recyclingEntityID);
		$this->suspendUtm($entityID, $recyclingEntityID);
		$this->suspendTracing($entityID, $recyclingEntityID);
		$this->suspendObservers($entityID, $recyclingEntityID);
		$this->suspendWaitings($entityID, $recyclingEntityID);
		$this->suspendChats($entityID, $recyclingEntityID);
		$this->suspendProductRows($entityID, $recyclingEntityID);
		$this->suspendScoringHistory($entityID, $recyclingEntityID);
		$this->suspendCustomRelations((int)$entityID, (int)$recyclingEntityID);
		$this->suspendBadges((int)$entityID, (int)$recyclingEntityID);

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID(\CCrmOwnerType::Lead, $entityID, $recyclingEntityID);
			$relation->save();
		}
		LeadRelationManager::getInstance()->registerRecycleBin($recyclingEntityID, $entityID, $slots);
		//endregion
	}

	/**
	 * Recover entity from Recycle Bin.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional operation parameters.
	 * @return bool
	 * @throws Crm\Synchronization\UserFieldSynchronizationException
	 * @throws Main\AccessDeniedException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	public function recover($entityID, array $params = array())
	{
		if($entityID <= 0)
		{
			return false;
		}

		$recyclingEntityID = isset($params['ID']) ? (int)$params['ID'] : 0;
		if($recyclingEntityID <= 0)
		{
			return false;
		}

		$slots = isset($params['SLOTS']) ? $params['SLOTS'] : null;
		if(!is_array($slots))
		{
			return false;
		}

		$fields = isset($slots['FIELDS']) ? $slots['FIELDS'] : null;
		if(!(is_array($fields) && !empty($fields)))
		{
			return false;
		}

		unset($fields['ID'], $fields['COMPANY_ID'], $fields['CONTACT_ID'], $fields['CONTACT_IDS']);

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Lead, $entityID, $recyclingEntityID);
		$relationMap->build();

		LeadRelationManager::getInstance()->prepareRecoveryFields($fields, $relationMap);

		/*
		$contactIDs = isset($slots['CONTACT_IDS']) ? $slots['CONTACT_IDS'] : null;
		if(is_array($contactIDs) && !empty($contactIDs))
		{
			$fields['CONTACT_IDS'] = $contactIDs;
		}
		*/

		//region Convert User Fields from Suspended Type
		$userFields = $this->prepareRestoredUserFields($recyclingEntityID);
		if(!empty($userFields))
		{
			$fields = array_merge($fields, $userFields);
		}
		//endregion

		$fields = $this->prepareFields($fields);

		$entity = new \CCrmLead(false);
		$newEntityID = $entity->Add(
			$fields,
			true,
			array(
				'IS_RESTORATION' => true,
				'DISABLE_USER_FIELD_CHECK' => true,
				'PRESERVE_CONTENT_TYPE' => true,
			)
		);
		if($newEntityID <= 0)
		{
			return false;
		}

		//region Relation
		LeadRelationManager::getInstance()->recoverBindings($newEntityID, $relationMap);
		Relation::updateEntityID(\CCrmOwnerType::Lead, $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$this->eraseSuspendedUserFields($recyclingEntityID);

		$this->recoverMultiFields($recyclingEntityID, $newEntityID);
		$this->recoverAddresses($recyclingEntityID, $newEntityID);
		$this->recoverTimeline($recyclingEntityID, $newEntityID);
		$this->recoverDocuments($recyclingEntityID, $newEntityID);
		$this->recoverLiveFeed($recyclingEntityID, $newEntityID);
		$this->recoverUtm($recyclingEntityID, $newEntityID);
		$this->recoverTracing($recyclingEntityID, $newEntityID);
		$this->recoverObservers($recyclingEntityID, $newEntityID);
		$this->recoverWaitings($recyclingEntityID, $newEntityID);
		$this->recoverChats($recyclingEntityID, $newEntityID);
		$this->recoverProductRows($recyclingEntityID, $newEntityID);
		$this->recoverScoringHistory($recyclingEntityID, $newEntityID);
		$this->recoverCustomRelations((int)$recyclingEntityID, (int)$newEntityID);
		$this->recoverBadges((int)$recyclingEntityID, (int)$newEntityID);

		$this->recoverActivities($recyclingEntityID, $entityID, $newEntityID, $params, $relationMap);

		//region Relation
		Relation::unregisterRecycleBin($recyclingEntityID);
		Relation::deleteJunks();
		//endregion

		$this->rebuildSearchIndex($newEntityID);
		$this->startRecoveryWorkflows($newEntityID);
		//TODO: start automation???

		return true;
	}

	/**
	 * Recover entity multifields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverMultiFields($recyclingEntityID, $newEntityID)
	{
		$this->innerRecoverMultiFields($recyclingEntityID, $newEntityID);
		\CCrmLead::SynchronizeMultifieldMarkers($newEntityID);
	}

	/**
	 * Erase entity from Recycle Bin.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional operation parameters.
	 * @return void
	 * @throws Main\AccessDeniedException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	public function erase($entityID, array $params = array())
	{
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'entityID');
		}

		$recyclingEntityID = isset($params['ID']) ? (int)$params['ID'] : 0;
		if($recyclingEntityID <= 0)
		{
			throw new Main\ArgumentException('Could not find parameter named: "ID".', 'params');
		}

		/*
		$slots = isset($params['SLOTS']) ? $params['SLOTS'] : null;
		if(is_array($slots))
		{
		}
		*/

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Lead, $entityID, $recyclingEntityID);
		$relationMap->build();

		$this->eraseActivities($recyclingEntityID, $params, $relationMap);
		$this->eraseSuspendProductRows($recyclingEntityID);
		$this->eraseSuspendedMultiFields($recyclingEntityID);
		$this->eraseSuspendedAddresses($recyclingEntityID, array(Crm\EntityAddressType::Primary));
		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedDocuments($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedUtm($recyclingEntityID);
		$this->eraseSuspendedTracing($recyclingEntityID);
		$this->eraseSuspendedObservers($recyclingEntityID);
		$this->eraseSuspendedWaitings($recyclingEntityID);
		$this->eraseSuspendedChats($recyclingEntityID);
		$this->eraseSuspendedUserFields($recyclingEntityID);
		$this->eraseSuspendedScoringHistory($recyclingEntityID);
		$this->eraseSuspendedCustomRelations($recyclingEntityID);
		$this->eraseSuspendedBadges($recyclingEntityID);

		Relation::deleteByRecycleBin($recyclingEntityID);
	}

	/**
	 * Set correct values of standard fields
	 * @param array $fields
	 * @return array
	 */
	protected function prepareFields(array $fields): array
	{
		if (
			isset($fields['STATUS_ID'])
			&& !\CCrmLEad::IsStatusExists($fields['STATUS_ID'])
		)
		{
			// if old status does not exist, STATUS_ID should be empty to be defined automatically
			unset($fields['STATUS_ID']);
		}

		return $fields;
	}
}
