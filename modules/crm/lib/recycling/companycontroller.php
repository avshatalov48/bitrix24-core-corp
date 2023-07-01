<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class CompanyController extends BaseController
{
	use MultiFieldControllerMixin {
		recoverMultiFields as innerRecoverMultiFields;
	}

	use ActivityControllerMixin;
	use AddressControllerMixin;
	use RequisiteControllerMixin;

	/** @var CompanyController|null */
	protected static $instance = null;

	/**
	 * @return CompanyController|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyController();
		}
		return self::$instance;
	}

	public static function getFieldNames()
	{
		return array(
			'ID',
			'DATE_CREATE', 'DATE_MODIFY', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'ASSIGNED_BY_ID', 'LOGO', 'ADDRESS', 'ADDRESS_LEGAL', 'BANKING_DETAILS',
			'OPENED', 'LEAD_ID', 'COMMENTS', 'IS_MY_COMPANY',
			'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'REVENUE', 'CURRENCY_ID', 'EMPLOYEES',
			'WEBFORM_ID', 'ORIGINATOR_ID', 'ORIGIN_ID', 'ORIGIN_VERSION',
			'CATEGORY_ID'
		);
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	public function getSuspendedEntityTypeID()
	{
		return \CCrmOwnerType::SuspendedCompany;
	}

	/**
	 * Get recyclebin entity type name.
	 * @see \Bitrix\Crm\Integration\Recyclebin\Company::getEntityName
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		return 'crm_company';
	}

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		$entityTitle = Crm\Integration\Recyclebin\RecyclingManager::resolveEntityTitle(
			\CCrmOwnerType::Company,
			$entityID
		);

		return Main\Localization\Loc::getMessage(
			'CRM_COMPANY_CTRL_ACTIVITY_OWNER_NOT_FOUND',
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
		$dbResult = \CCrmCompany::GetListEx(
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

		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$slots['PARENT_LEAD_ID'] = (int)$fields['LEAD_ID'];
		}

		$contactIDs = Crm\Binding\ContactCompanyTable::getCompanyContactIDs($entityID);
		if(!empty($contactIDs))
		{
			$slots['CONTACT_IDS'] = $contactIDs;
		}

		$leadIDs = LeadBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Company, $entityID);
		if(!empty($leadIDs))
		{
			$slots['LEAD_IDS'] = $leadIDs;
		}

		$dealIDs = DealBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Company, $entityID);
		if(!empty($dealIDs))
		{
			$slots['DEAL_IDS'] = $dealIDs;
		}

		$quoteIDs = QuoteBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Company, $entityID);
		if(!empty($quoteIDs))
		{
			$slots['QUOTE_IDS'] = $quoteIDs;
		}

		$invoiceIDs = InvoiceBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Company, $entityID);
		if(!empty($invoiceIDs))
		{
			$slots['INVOICE_IDS'] = $invoiceIDs;
		}

		$orderIds = OrderBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Company, $entityID);
		if(!empty($orderIds))
		{
			$slots['ORDER_IDS'] = $orderIds;
		}

		$storeDocumentIds = StoreDocumentBinder::getInstance()->getBoundEntityIDs(
			\CCrmOwnerType::Company,
			$entityID
		);
		if(!empty($storeDocumentIds))
		{
			$slots['STORE_DOCUMENT_IDS'] = $storeDocumentIds;
		}

		$slots = array_merge(
			$slots,
			DynamicBinderManager::getInstance()
				->configure($entityID, \CCrmOwnerType::Company)
				->getData()
		);

		$requisiteLinks = Crm\EntityRequisite::getLinksByOwner(\CCrmOwnerType::Company, $entityID);
		if(!empty($requisiteLinks))
		{
			$slots['REQUISITE_LINKS'] = $requisiteLinks;
		}

		$slots = array_merge($slots, $this->prepareActivityData($entityID, $params));

		return array(
			'TITLE' => \CCrmOwnerType::GetCaption(
				\CCrmOwnerType::Company,
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

		$recyclingEntity = Crm\Integration\Recyclebin\Company::createRecycleBinEntity($entityID);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : array();
		$relations = CompanyRelationManager::getInstance()->buildCollection($entityID, $slots);
		foreach($slots as $slotKey => $slotData)
		{
			$recyclingEntity->add($slotKey, $slotData);
		}

		//region Files
		if(isset($fields['LOGO']) && $fields['LOGO'] > 0)
		{
			$recyclingEntity->addFile($fields['LOGO'], Crm\Integration\StorageType::FileName);
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
			QuoteBinder::getInstance()->unbindEntities(\CCrmOwnerType::Company, $entityID, $slots['QUOTE_IDS']);
		}

		if(isset($slots['INVOICE_IDS']) && is_array($slots['INVOICE_IDS']))
		{
			InvoiceBinder::getInstance()->unbindEntities(\CCrmOwnerType::Company, $entityID, $slots['INVOICE_IDS']);
		}

		DynamicBinderManager::getInstance()
			->configure($entityID, \CCrmOwnerType::Company)
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
		$this->suspendCustomRelations((int)$entityID, (int)$recyclingEntityID);
		$this->suspendBadges((int)$entityID, (int)$recyclingEntityID);

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID(\CCrmOwnerType::Company, $entityID, $recyclingEntityID);
			$relation->save();
		}
		CompanyRelationManager::getInstance()->registerRecycleBin($recyclingEntityID, $entityID, $slots);
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
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\ObjectPropertyException
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

		unset($fields['ID'], $fields['CONTACT_ID'], $fields['LEAD_ID']);

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Company, $entityID, $recyclingEntityID);
		$relationMap->build();

		CompanyRelationManager::getInstance()->prepareRecoveryFields($fields, $relationMap);

		//region Convert User Fields from Suspended Type
		$userFields = $this->prepareRestoredUserFields($recyclingEntityID);
		if(!empty($userFields))
		{
			$fields = array_merge($fields, $userFields);
		}
		//endregion

		$entity = new \CCrmCompany(false);
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

		//region Relations
		CompanyRelationManager::getInstance()->recoverBindings($newEntityID, $relationMap);
		Relation::updateEntityID(\CCrmOwnerType::Company, $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$quoteIDs = isset($slots['QUOTE_IDS']) ? $slots['QUOTE_IDS'] : null;
		if(is_array($quoteIDs))
		{
			QuoteBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$newEntityID,
				Crm\Entity\Quote::selectExisted($quoteIDs)
			);
		}

		$invoiceIDs = isset($slots['INVOICE_IDS']) ? $slots['INVOICE_IDS'] : null;
		if(is_array($invoiceIDs))
		{
			InvoiceBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$newEntityID,
				$invoiceIDs
			);
		}

		$orderIds = isset($slots['ORDER_IDS']) ? $slots['ORDER_IDS'] : null;
		if(is_array($orderIds) && !empty($orderIds))
		{
			OrderBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$newEntityID,
				$orderIds
			);
		}

		$storeDocumentIds = isset($slots['STORE_DOCUMENT_IDS']) ? $slots['STORE_DOCUMENT_IDS'] : null;
		if(is_array($storeDocumentIds) && !empty($storeDocumentIds))
		{
			StoreDocumentBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Company,
				$newEntityID,
				$storeDocumentIds
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
		$this->recoverCustomRelations((int)$recyclingEntityID, (int)$newEntityID);
		$this->recoverBadges((int)$recyclingEntityID, (int)$newEntityID);

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
		\CCrmCompany::SynchronizeMultifieldMarkers($newEntityID);
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

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Company, $entityID, $recyclingEntityID);
		$relationMap->build();

		$this->eraseActivities($recyclingEntityID, $params, $relationMap);
		$this->eraseSuspendedMultiFields($recyclingEntityID);
		$this->eraseSuspendedAddresses(
			$recyclingEntityID,
			array(Crm\EntityAddressType::Primary, Crm\EntityAddressType::Registered)
		);
		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedDocuments($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedRequisites($recyclingEntityID);
		$this->eraseSuspendedUtm($recyclingEntityID);
		$this->eraseSuspendedTracing($recyclingEntityID);
		$this->eraseSuspendedUserFields($recyclingEntityID);
		$this->eraseSuspendedCustomRelations($recyclingEntityID);
		$this->eraseSuspendedBadges($recyclingEntityID);

		//region Files
		if(isset($params['FILES']) && is_array($params['FILES']) && !empty($params['FILES']))
		{
			$this->eraseFiles($params['FILES']);
		}
		//endregion

		Relation::deleteByRecycleBin($recyclingEntityID);
	}
}
