<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

class DealController extends BaseController
{
	use ActivityControllerMixin;
	use ProductRowControllerMixin;
	use ObserverControllerMixin;
	use ChatControllerMixin;
	use WaitingControllerMixin;

	/** @var DealController|null */
	protected static $instance = null;

	/**
	 * @return DealController|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DealController();
		}
		return self::$instance;
	}

	public static function getFieldNames()
	{
		return array(
			'ID',
			'DATE_CREATE', 'DATE_MODIFY', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'ASSIGNED_BY_ID', 'OPENED', 'LEAD_ID', 'COMPANY_ID', 'CONTACT_ID', 'QUOTE_ID',
			'TITLE', 'CATEGORY_ID', 'STAGE_ID', 'IS_RECURRING', 'IS_RETURN_CUSTOMER',
			'CLOSED', 'TYPE_ID', 'CURRENCY_ID', 'OPPORTUNITY', 'TAX_VALUE', 'PROBABILITY',
			'COMMENTS', 'BEGINDATE', 'CLOSEDATE',
			'LOCATION_ID', 'WEBFORM_ID', 'SOURCE_ID', 'SOURCE_DESCRIPTION',
			'ORIGINATOR_ID', 'ORIGIN_ID',
			'ADDITIONAL_INFO'
		);
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	public function getSuspendedEntityTypeID()
	{
		return \CCrmOwnerType::SuspendedDeal;
	}

	/**
	 * Get recyclebin entity type name.
	 * @see \Bitrix\Crm\Integration\Recyclebin\Deal::getEntityName
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		return 'crm_deal';
	}

	//region ProductRowController
	/**
	 * Get Product Row Owner Type
	 * @return string
	 */
	public function getProductRowOwnerType()
	{
		return 'D';
	}

	/**
	 * Get Product Row Suspended Owner Type
	 * @return string
	 */
	public function getProductRowSuspendedOwnerType()
	{
		return 'SD';
	}
	//endregion

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		$entityTitle = Crm\Integration\Recyclebin\RecyclingManager::resolveEntityTitle(
			\CCrmOwnerType::Deal,
			$entityID
		);

		return Main\Localization\Loc::getMessage(
			'CRM_DEAL_CTRL_ACTIVITY_OWNER_NOT_FOUND',
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
		$dbResult = \CCrmDeal::GetListEx(
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

		$slots = array('FIELDS' => array_intersect_key($fields, array_flip(self::getFieldNames())));

		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$slots['PARENT_LEAD_ID'] = (int)$fields['LEAD_ID'];
		}

		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$slots['COMPANY_ID'] = $companyID;
		}

		$contactIDs = Crm\Binding\DealContactTable::getDealContactIDs($entityID);
		if(!empty($contactIDs))
		{
			$slots['CONTACT_IDS'] = $contactIDs;
		}

		$quoteIDs = QuoteBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Deal, $entityID);
		if(!empty($quoteIDs))
		{
			$slots['QUOTE_IDS'] = $quoteIDs;
		}

		$invoiceIDs = InvoiceBinder::getInstance()->getBoundEntityIDs(\CCrmOwnerType::Deal, $entityID);
		if(!empty($invoiceIDs))
		{
			$slots['INVOICE_IDS'] = $invoiceIDs;
		}

		$requisiteLinks = Crm\EntityRequisite::getLinks(\CCrmOwnerType::Deal, $entityID);
		if(!empty($requisiteLinks))
		{
			$slots['REQUISITE_LINKS'] = $requisiteLinks;
		}

		$slots = array_merge($slots, $this->prepareActivityData($entityID, $params));

		return array(
			'TITLE' => \CCrmOwnerType::GetCaption(
				\CCrmOwnerType::Deal,
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
	 * @throws Main\NotSupportedException
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

		$recyclingEntity = Crm\Integration\Recyclebin\Deal::createRecycleBinEntity($entityID);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : array();
		$relations = DealRelationManager::getInstance()->buildCollection($entityID, $slots);
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

		if(isset($slots['QUOTE_IDS']) && is_array($slots['QUOTE_IDS']))
		{
			QuoteBinder::getInstance()->unbindEntities(\CCrmOwnerType::Deal, $entityID, $slots['QUOTE_IDS']);
		}

		if(isset($slots['INVOICE_IDS']) && is_array($slots['INVOICE_IDS']))
		{
			InvoiceBinder::getInstance()->unbindEntities(\CCrmOwnerType::Deal, $entityID, $slots['INVOICE_IDS']);
		}

		$this->suspendActivities($entityData, $entityID, $recyclingEntityID);
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

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID(\CCrmOwnerType::Deal, $entityID, $recyclingEntityID);
			$relation->save();
		}
		DealRelationManager::getInstance()->registerRecycleBin($recyclingEntityID, $entityID, $slots);
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

		unset($fields['ID'], $fields['COMPANY_ID'], $fields['CONTACT_ID'], $fields['CONTACT_IDS'], $fields['LEAD_ID']);

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Deal, $entityID, $recyclingEntityID);
		$relationMap->build();

		DealRelationManager::getInstance()->prepareRecoveryFields($fields, $relationMap);

		//region Convert User Fields from Suspended Type
		$userFields = $this->prepareRestoredUserFields($recyclingEntityID);
		if(!empty($userFields))
		{
			$fields = array_merge($fields, $userFields);
		}
		//endregion

		$entity = new \CCrmDeal(false);
		$newEntityID = $entity->Add(
			$fields,
			true,
			array(
				'IS_RESTORATION' => true,
				'DISABLE_USER_FIELD_CHECK' => true
			)
		);
		if($newEntityID <= 0)
		{
			return false;
		}

		//region Relation
		DealRelationManager::getInstance()->recoverBindings($newEntityID, $relationMap);
		Relation::updateEntityID(\CCrmOwnerType::Deal, $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$quoteIDs = isset($slots['QUOTE_IDS']) ? $slots['QUOTE_IDS'] : null;
		if(is_array($quoteIDs))
		{
			QuoteBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Deal,
				$newEntityID,
				Crm\Entity\Quote::selectExisted($quoteIDs)
			);
		}

		$invoiceIDs = isset($slots['INVOICE_IDS']) ? $slots['INVOICE_IDS'] : null;
		if(is_array($invoiceIDs))
		{
			InvoiceBinder::getInstance()->bindEntities(
				\CCrmOwnerType::Deal,
				$newEntityID,
				$invoiceIDs
			);
		}

		$this->eraseSuspendedUserFields($recyclingEntityID);

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

		$requisiteLinks = isset($slots['REQUISITE_LINKS']) ? $slots['REQUISITE_LINKS'] : null;
		if(is_array($requisiteLinks) && !empty($requisiteLinks))
		{
			for($i = 0, $length = count($requisiteLinks); $i < $length; $i++)
			{
				$requisiteLinks[$i]['ENTITY_TYPE_ID'] = \CCrmOwnerType::Deal;
				$requisiteLinks[$i]['ENTITY_ID'] = $newEntityID;
			}
			Crm\EntityRequisite::setLinks($requisiteLinks);
		}
		$this->recoverActivities($recyclingEntityID, $entityID, $newEntityID, $params, $relationMap);

		//region Relation
		Relation::unregisterRecycleBin($recyclingEntityID);
		Relation::deleteJunks();
		//endregion

		$this->rebuildSearchIndex($newEntityID);
		$this->startRecoveryWorkflows($newEntityID);

		return true;
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

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Deal, $entityID, $recyclingEntityID);
		$relationMap->build();

		$this->eraseActivities($recyclingEntityID, $params, $relationMap);
		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedDocuments($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedUtm($recyclingEntityID);
		$this->eraseSuspendedTracing($recyclingEntityID);
		$this->eraseSuspendedObservers($recyclingEntityID);
		$this->eraseSuspendedWaitings($recyclingEntityID);
		$this->eraseSuspendedChats($recyclingEntityID);
		$this->eraseSuspendProductRows($recyclingEntityID);
		$this->eraseSuspendedUserFields($recyclingEntityID);
		$this->eraseSuspendedScoringHistory($recyclingEntityID);

		Relation::deleteByRecycleBin($recyclingEntityID);
	}
}
