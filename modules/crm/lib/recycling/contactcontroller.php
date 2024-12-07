<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class ContactController extends BaseController
{
	use MultiFieldControllerMixin {
		recoverMultiFields as innerRecoverMultiFields;
	}

	use ActivityControllerMixin;
	use AddressControllerMixin;
	use RequisiteControllerMixin;
	use ObserverControllerMixin;

	/** @var ContactController|null  */
	protected static $instance = null;
	/**
	 * @return ContactController|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactController();
		}
		return self::$instance;
	}

	public static function getFieldNames()
	{
		return array(
			'ID',
			'DATE_CREATE', 'DATE_MODIFY', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'ASSIGNED_BY_ID', 'PHOTO', 'ADDRESS', 'EXPORT', 'OPENED',
			'LEAD_ID', 'COMPANY_ID', 'TYPE_ID', 'SOURCE_ID', 'SOURCE_DESCRIPTION',
			'HONORIFIC', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'BIRTHDATE', 'POST', 'COMMENTS',
			'WEBFORM_ID', 'FACE_ID', 'ORIGINATOR_ID', 'ORIGIN_ID', 'ORIGIN_VERSION',
			'CATEGORY_ID'
		);
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Contact;
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	public function getSuspendedEntityTypeID()
	{
		return \CCrmOwnerType::SuspendedContact;
	}

	/**
	 * Get recyclebin entity type name.
	 * @see \Bitrix\Crm\Integration\Recyclebin\Contact::getEntityName
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		return 'crm_contact';
	}

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		$entityTitle = Crm\Integration\Recyclebin\RecyclingManager::resolveEntityTitle(
			\CCrmOwnerType::Contact,
			$entityID
		);

		return Main\Localization\Loc::getMessage(
			'CRM_CONTACT_CTRL_ACTIVITY_OWNER_NOT_FOUND',
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
		$dbResult = \CCrmContact::GetListEx(
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
			'FIELDS' => array_intersect_key($fields, array_flip(self::getFieldNames())),
		];

		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$slots['PARENT_LEAD_ID'] = (int)$fields['LEAD_ID'];
		}

		$companyIDs = Crm\Binding\ContactCompanyTable::getContactCompanyIDs($entityID);
		if(!empty($companyIDs))
		{
			$slots['COMPANY_IDS'] = $companyIDs;
		}

		$leadIDs = Crm\Binding\LeadContactTable::getContactLeadIDs($entityID);
		if(!empty($leadIDs))
		{
			$slots['LEAD_IDS'] = $leadIDs;
		}

		$dealIDs = DealBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Contact, $entityID);
		if(!empty($dealIDs))
		{
			$slots['DEAL_IDS'] = $dealIDs;
		}

		$quoteIDs = QuoteBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Contact, $entityID);
		if(!empty($quoteIDs))
		{
			$slots['QUOTE_IDS'] = $quoteIDs;
		}

		$invoiceIDs = InvoiceBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Contact, $entityID);
		if(!empty($invoiceIDs))
		{
			$slots['INVOICE_IDS'] = $invoiceIDs;
		}

		$orderIds = OrderBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Contact, $entityID);
		if(!empty($orderIds))
		{
			$slots['ORDER_IDS'] = $orderIds;
		}

		$storeDocumentIds = StoreDocumentBinder::getInstance()->getBoundEntityIDs(
			\CCrmOwnerType::Contact,
			$entityID
		);
		if(!empty($storeDocumentIds))
		{
			$slots['STORE_DOCUMENT_IDS'] = $storeDocumentIds;
		}

		$agentContractIds = AgentContractBinder::getInstance()->getBoundEntityIDs(
			\CCrmOwnerType::Contact,
			$entityID
		);
		if(!empty($agentContractIds))
		{
			$slots['AGENT_CONTRACT_IDS'] = $agentContractIds;
		}

		$slots = array_merge(
			$slots,
			DynamicBinderManager::getInstance()
				->configure($entityID, \CCrmOwnerType::Contact)
				->getData()
		);

		$requisiteLinks = Crm\EntityRequisite::getLinksByOwner(\CCrmOwnerType::Contact, $entityID);
		if(!empty($requisiteLinks))
		{
			$slots['REQUISITE_LINKS'] = $requisiteLinks;
		}

		$slots = array_merge($slots, $this->prepareActivityData($entityID, $params));

		return array(
			'TITLE' => \CCrmOwnerType::GetCaption(
				\CCrmOwnerType::Contact,
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

		$recyclingEntity = Crm\Integration\Recyclebin\Contact::createRecycleBinEntity($entityID);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : array();
		$relations = ContactRelationManager::getInstance()->buildCollection($entityID, $slots);
		foreach($slots as $slotKey => $slotData)
		{
			$recyclingEntity->add($slotKey, $slotData);
		}

		//region Files
		if(isset($fields['PHOTO']) && $fields['PHOTO'] > 0)
		{
			$recyclingEntity->addFile($fields['PHOTO'], Crm\Integration\StorageType::FileName);

		}
		//endregion

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

		if(isset($slots['QUOTE_IDS']) && is_array($slots['QUOTE_IDS']))
		{
			QuoteBinder::getInstance()->unbindEntities(\CCrmOwnerType::Contact, $entityID, $slots['QUOTE_IDS']);
		}

		if(isset($slots['INVOICE_IDS']) && is_array($slots['INVOICE_IDS']))
		{
			InvoiceBinder::getInstance()->unbindEntities(\CCrmOwnerType::Contact, $entityID, $slots['INVOICE_IDS']);
		}

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Contact)
			->unbindEntities($slots);

		$this->suspendActivities($entityData, $entityID, $recyclingEntityID);
		$this->suspendMultiFields($entityID, $recyclingEntityID);
		$this->suspendAddresses($entityID, $recyclingEntityID);
		$this->suspendTimeline($entityID, $recyclingEntityID);
		$this->suspendDocuments($entityID, $recyclingEntityID);
		$this->suspendLiveFeed($entityID, $recyclingEntityID);
		$this->suspendRequisites($entityID, $recyclingEntityID);
		$this->suspendUtm($entityID, $recyclingEntityID);
		$this->suspendTracing($entityID, $recyclingEntityID);
		$this->suspendObservers($entityID, $recyclingEntityID);
		$this->suspendCustomRelations((int)$entityID, (int)$recyclingEntityID);
		$this->suspendBadges((int)$entityID, (int)$recyclingEntityID);
		\Bitrix\Crm\Integration\AI\EventHandler::onItemMoveToBin(
			new Crm\ItemIdentifier($this->getEntityTypeID(), $entityID),
			new Crm\ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityID),
		);

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID(\CCrmOwnerType::Contact, $entityID, $recyclingEntityID);
			$relation->save();
		}
		ContactRelationManager::getInstance()->registerRecycleBin($recyclingEntityID, $entityID, $slots);
		//endregion

		\CCrmEntitySelectorHelper::clearPrepareRequisiteDataCacheByEntity(\CCrmOwnerType::Contact, $entityID);
	}

	public function recover(int $entityID, array $params = []): ?int
	{
		if($entityID <= 0)
		{
			return null;
		}

		$recyclingEntityID = isset($params['ID']) ? (int)$params['ID'] : 0;
		if($recyclingEntityID <= 0)
		{
			return null;
		}

		$slots = isset($params['SLOTS']) ? $params['SLOTS'] : null;
		if(!is_array($slots))
		{
			return null;
		}

		$fields = isset($slots['FIELDS']) ? $slots['FIELDS'] : null;
		if(!(is_array($fields) && !empty($fields)))
		{
			return null;
		}

		unset($fields['ID'], $fields['COMPANY_ID'], $fields['COMPANY_IDS'], $fields['LEAD_ID']);

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Contact, $entityID, $recyclingEntityID);
		$relationMap->build();

		ContactRelationManager::getInstance()->prepareRecoveryFields($fields, $relationMap);

		//region Convert User Fields from Suspended Type
		$userFields = $this->prepareRestoredUserFields($recyclingEntityID);
		if(!empty($userFields))
		{
			$fields = array_merge($fields, $userFields);
		}
		//endregion

		$entity = new \CCrmContact(false);
		$newEntityID = $entity->Add(
			$fields,
			true,
			array(
				'IS_RESTORATION' => true,
				'DISABLE_USER_FIELD_CHECK' => true,
			)
		);
		if($newEntityID <= 0)
		{
			return null;
		}

		//region Relations
		ContactRelationManager::getInstance()->recoverBindings($newEntityID, $relationMap);
		Relation::updateEntityID(\CCrmOwnerType::Contact, $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$quoteIDs = isset($slots['QUOTE_IDS']) ? $slots['QUOTE_IDS'] : null;
		if(is_array($quoteIDs))
		{
			QuoteBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
				$newEntityID,
				Crm\Entity\Quote::selectExisted($quoteIDs)
			);
		}

		$invoiceIDs = isset($slots['INVOICE_IDS']) ? $slots['INVOICE_IDS'] : null;
		if(is_array($invoiceIDs) && !empty($invoiceIDs))
		{
			InvoiceBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
				$newEntityID,
				$invoiceIDs
			);
		}

		$orderIds = isset($slots['ORDER_IDS']) ? $slots['ORDER_IDS'] : null;
		if(is_array($orderIds) && !empty($orderIds))
		{
			OrderBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
				$newEntityID,
				$orderIds
			);
		}

		$storeDocumentIds = isset($slots['STORE_DOCUMENT_IDS']) ? $slots['STORE_DOCUMENT_IDS'] : null;
		if(is_array($storeDocumentIds) && !empty($storeDocumentIds))
		{
			StoreDocumentBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
				$newEntityID,
				$storeDocumentIds
			);
		}

		$agentContractIds = isset($slots['AGENT_CONTRACT_IDS']) ? $slots['AGENT_CONTRACT_IDS'] : null;
		if(is_array($agentContractIds) && !empty($agentContractIds))
		{
			AgentContractBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Contact,
				$newEntityID,
				$agentContractIds
			);
		}

		$this->eraseSuspendedUserFields($recyclingEntityID);

		$this->recoverMultiFields($recyclingEntityID, $newEntityID);
		$this->recoverAddresses($recyclingEntityID, $newEntityID);
		$this->recoverTimeline($recyclingEntityID, $newEntityID);
		$this->recoverDocuments($recyclingEntityID, $newEntityID);
		$this->recoverLiveFeed($recyclingEntityID, $newEntityID);
		$this->recoverRequisites($recyclingEntityID, $newEntityID);
		$this->recoverUtm($recyclingEntityID, $newEntityID);
		$this->recoverTracing($recyclingEntityID, $newEntityID);
		$this->recoverObservers($recyclingEntityID, $newEntityID);
		$this->recoverCustomRelations((int)$recyclingEntityID, (int)$newEntityID);
		$this->recoverBadges((int)$recyclingEntityID, (int)$newEntityID);
		\Bitrix\Crm\Integration\AI\EventHandler::onItemRestoreFromRecycleBin(
			new Crm\ItemIdentifier($this->getEntityTypeID(), $newEntityID),
			new Crm\ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityID),
		);

		$requisiteLinks = isset($slots['REQUISITE_LINKS']) ? $slots['REQUISITE_LINKS'] : null;
		if(is_array($requisiteLinks) && !empty($requisiteLinks))
		{
			Crm\EntityRequisite::setLinks($requisiteLinks);
		}
		$this->recoverActivities($recyclingEntityID, $entityID, $newEntityID, $params, $relationMap);

		//region Relations
		Relation::unregisterRecycleBin($recyclingEntityID);
		Relation::deleteJunks();
		//endregion

		$this->rebuildSearchIndex($newEntityID);
		$this->startRecoveryWorkflows($newEntityID);

		return $newEntityID;
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
		\CCrmContact::SynchronizeMultifieldMarkers($newEntityID);
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
			$fields = isset($slots['FIELDS']) ? $slots['FIELDS'] : null;
		}
		*/

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Contact, $entityID, $recyclingEntityID);
		$relationMap->build();

		$this->eraseActivities($recyclingEntityID, $params, $relationMap);
		$this->eraseSuspendedMultiFields($recyclingEntityID);
		$this->eraseSuspendedAddresses($recyclingEntityID, array(Crm\EntityAddressType::Primary));
		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedDocuments($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedRequisites($recyclingEntityID);
		$this->eraseSuspendedUtm($recyclingEntityID);
		$this->eraseSuspendedTracing($recyclingEntityID);
		$this->eraseSuspendedObservers($recyclingEntityID);
		$this->eraseSuspendedUserFields($recyclingEntityID);
		$this->eraseSuspendedCustomRelations($recyclingEntityID);
		$this->eraseSuspendedBadges($recyclingEntityID);
		\Bitrix\Crm\Integration\AI\EventHandler::onItemDelete(
			new Crm\ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityID),
		);

		//region Files
		if(isset($params['FILES']) && is_array($params['FILES']) && !empty($params['FILES']))
		{
			$this->eraseFiles($params['FILES']);
		}
		//endregion

		Relation::deleteByRecycleBin($recyclingEntityID);
	}
}
