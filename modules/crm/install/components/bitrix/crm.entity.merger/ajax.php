<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class CCrmEntityMergeComponentAjaxController extends Main\Engine\Controller
{
	protected $currentUser = null;
	protected $currentUserId = 0;
	protected $currentUserPermissions = null;
	/** @var CCrmEntityMergerComponent|null */
	protected $component = null;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		CModule::IncludeModule('crm');
		if(!Crm\Security\EntityAuthorization::isAuthorized())
		{
			$this->addError(new Main\Error('Access denied.'));
			return false;
		}

		$this->currentUser = \CCrmSecurityHelper::GetCurrentUser();
		$this->currentUserId = (int)$this->currentUser->GetID();
		$this->currentUserPermissions = \CCrmPerms::GetUserPermissions($this->currentUserId);

		return parent::processBeforeAction($action);
	}

	protected function getComponent()
	{
		if(!$this->component)
		{
			CBitrixComponent::includeComponentClass('bitrix:crm.entity.merger');
			$this->component = new CCrmEntityMergerComponent();
		}
		return $this->component;
	}

	public function getDedupeQueueItemAction($entityTypeName, array $typeNames, $scope, $offset)
	{
		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
		$offset = (int)$offset;

		$result = [];

		$component = $this->getComponent();
		$component->setEntityTypeID($entityTypeID);
		$component->prepareDedupeData($typeNames, $scope, $offset);
		$component->prepareEntityInfos();

		$result['ENTITY_IDS'] = $component->arResult['ENTITY_IDS'];
		$result['ENTITY_INFOS'] = $component->arResult['ENTITY_INFOS'];

		$result['CRITERION_DATA'] = $component->arResult['DEDUPE_CRITERION_DATA'];
		$result['QUEUE_INFO'] = $component->arResult['DEDUPE_QUEUE_INFO'];

		return $result;
	}
	public function mergeDedupeQueueItemAction($entityTypeName, array $typeNames, $scope, $offset, array $seedEntityIds, $targEntityId, array $map)
	{
		$typeIDs = array();
		foreach($typeNames as $typeName)
		{
			$typeID = Crm\Integrity\DuplicateIndexType::resolveID($typeName);
			if($typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED)
			{
				$typeIDs[] = $typeID;
			}
		}

		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);

		$component = $this->getComponent();
		$component->setEntityTypeID($entityTypeID);
		$list = $component->getDedupeQueueList($typeIDs, $scope);
		if($this->mergeAction($entityTypeName, $seedEntityIds, $targEntityId, $map))
		{
			return ['QUEUE_INFO' => [ 'length' => $list ? $list->getRootItemCount() : 0, 'offset' => (int)$offset ] ];
		}

		return false;
	}
	public function postponeDedupeItemAction($entityTypeName, $typeId, array $matches, $scope)
	{
		$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		$typeId = (int)$typeId;
		if(!Crm\Integrity\DuplicateIndexType::isDefined($typeId))
		{
			$this->addError(new Main\Error('Type name is not specified or invalid.'));
			return false;
		}

		$criterion = Crm\Integrity\DuplicateManager::createCriterion($typeId, $matches);

		\Bitrix\Crm\Integrity\DuplicateIndexBuilder::setStatusID(
			$this->currentUserId,
			$entityTypeID,
			$criterion->getIndexTypeID(),
			$criterion->getMatchHash(),
			$scope,
			Crm\Integrity\DuplicateStatus::POSTPONED
		);

		return true;
	}
	public function markAsNonDuplicatesAction($entityTypeName, $leftEntityID, $rightEntityID, $indexType, array $matches)
	{
		$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		if($leftEntityID <= 0)
		{
			$this->addError(new Main\Error('Left Entity ID is not specified or invalid.'));
			return false;
		}

		if($rightEntityID <= 0)
		{
			$this->addError(new Main\Error('Right Entity ID is not specified or invalid.'));
			return false;
		}

		if(!is_int($indexType))
		{
			$indexType = (int)$indexType;
		}
		if(!Crm\Integrity\DuplicateIndexType::isDefined($indexType))
		{
			$this->addError(new Main\Error('Deduplication Index Type is not specified or invalid.'));
			return false;
		}

		if(empty($matches))
		{
			$this->addError(new Main\Error('Deduplication Matches is not specified or invalid.'));
			return false;
		}

		$enablePermissionCheck = !CCrmPerms::IsAdmin($this->currentUserId);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $this->currentUserId, $enablePermissionCheck);

		$criterion = Crm\Integrity\DuplicateManager::createCriterion($indexType, $matches);
		try
		{
			$merger->registerCriterionMismatch($criterion, $leftEntityID, $rightEntityID);
			$builder = Crm\Integrity\DuplicateManager::createIndexBuilder($indexType, $entityTypeID, $this->currentUserId, $enablePermissionCheck);
			$builder->processMismatchRegistration($criterion);
		}
		catch(Exception $e)
		{
			$this->addError(new Main\Error($e->getMessage()));
			return false;
		}

		return true;
	}
	public function mergeAction($entityTypeName, array $seedEntityIds, $targEntityId, array $map)
	{
		$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		if(empty($seedEntityIds))
		{
			$this->addError(new Main\Error('Seed Entity Ids are empty.'));
			return false;
		}

		if($targEntityId <= 0)
		{
			$this->addError(new Main\Error('Target Entity Id is not defined or invalid.'));
			return false;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->currentUserId);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $this->currentUserId, $enablePermissionCheck);
		$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::ASK_USER);
		if($map !== null)
		{
			$merger->setMap($map);
		}
		try
		{
			$merger->mergeBatch($seedEntityIds, $targEntityId);
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			$this->addError(new Main\Error($e->getLocalizedMessage()));
			return false;
		}
		catch(\Exception $e)
		{
			$this->addError(new Main\Error($e->getMessage()));
			return false;
		}

		return true;
	}
	public function postponeQueueItemAction($entityTypeName, $queueName, array $queueData, $queueIndex)
	{
		if(!is_string($queueName))
		{
			$queueName = (string)$queueName;
		}

		if($queueName === "")
		{
			$this->addError(new Main\Error('Queue Name is not defined.'));
			return false;
		}

		$queueItems = isset($queueData['ITEMS']) && is_array($queueData['ITEMS']) ? $queueData['ITEMS'] : array();
		if($queueIndex >= count($queueItems))
		{
			$this->addError(new Main\Error('Queue Index must be less than Queue length.'));
			return false;
		}

		$removedItems = array_splice($queueItems, $queueIndex, 1);
		if(!empty($removedItems))
		{
			$queueItems[] = $removedItems[0];
		}

		$queueData['ITEMS'] = $queueItems;
		CUserOptions::SetOption('crm', $queueName, $queueData, false, $this->currentUserId);
		return [ 'QUEUE_DATA' => $queueData ];
	}
	public function mergeQueueItemAction($entityTypeName, $queueName, array $queueData, $queueIndex, array $map)
	{
		$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		if(!is_string($queueName))
		{
			$queueName = (string)$queueName;
		}

		if($queueName === "")
		{
			$this->addError(new Main\Error('Queue Name is not defined.'));
			return false;
		}

		if(!is_int($queueIndex))
		{
			$queueIndex = (int)$queueIndex;
		}

		$queueItems = isset($queueData['ITEMS']) && is_array($queueData['ITEMS']) ? $queueData['ITEMS'] : array();
		if($queueIndex >= count($queueItems))
		{
			$this->addError(new Main\Error('Queue Index must be less than Queue length.'));
			return false;
		}

		$queueItem = $queueItems[$queueIndex];
		if(!is_array($queueItem))
		{
			$this->addError(new Main\Error('Queue Item is invalid.'));
			return false;
		}

		$rootEntityID = isset($queueItem['ROOT_ENTITY_ID']) ? (int)$queueItem['ROOT_ENTITY_ID'] : 0;
		if($rootEntityID <= 0)
		{
			$this->addError(new Main\Error('Root Entity ID is not specified or invalid.'));
			return false;
		}

		$entityIDs = isset($queueItem['ENTITY_IDS']) && is_array($queueItem['ENTITY_IDS']) ? $queueItem['ENTITY_IDS'] : [];
		if(empty($entityIDs))
		{
			$this->addError(new Main\Error('Entity IDs are not specified.'));
			return false;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->currentUserId);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $this->currentUserId, $enablePermissionCheck);
		$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::ASK_USER);
		if($map !== null)
		{
			$merger->setMap($map);
		}
		try
		{
			$merger->mergeBatch($entityIDs, $rootEntityID);
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			$this->addError(new Main\Error($e->getLocalizedMessage()));
			return false;
		}
		catch(\Exception $e)
		{
			$this->addError(new Main\Error($e->getMessage()));
			return false;
		}

		//Checking for presence of queued entities.
		$queueItems[$queueIndex]['EXECUTED'] = true;
		for($i = $queueIndex + 1, $length = count($queueItems); $i < $length; $i++)
		{
			$rootEntityID = isset($queueItems[$i]['ROOT_ENTITY_ID']) ? (int)$queueItems[$i]['ROOT_ENTITY_ID'] : 0;
			$entityIDs = isset($queueItems[$i]['ENTITY_IDS']) && is_array($queueItems[$i]['ENTITY_IDS']) ? $queueItems[$i]['ENTITY_IDS'] : [];
			$effectiveEntityIDs = Crm\Entity\EntityManager::selectExisted($entityTypeID, array_merge(array($rootEntityID), $entityIDs));

			if(count($effectiveEntityIDs) < 2)
			{
				$queueItems[$i]['EXECUTED'] = true;
			}
		}
		$queueData['ITEMS'] = $queueItems;
		CUserOptions::SetOption('crm', $queueName, $queueData, false, $this->currentUserId);
		return [ 'QUEUE_DATA' => $queueData ];
	}
	public function prepareMergeDataAction($entityTypeName, array $seedEntityIds, $targEntityId)
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		$seedEntityIds = array_map("intval", $seedEntityIds);
		if(empty($seedEntityIds))
		{
			$this->addError(new Main\Error('The parameter seedEntityIds is required.'));
			return null;
		}

		$targEntityId = (int)$targEntityId;
		if($targEntityId <= 0)
		{
			$this->addError(new Main\Error('The parameter targEntityId is required.'));
			return null;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->currentUserId);
		$merger = Crm\Merger\EntityMerger::create($entityTypeId, $this->currentUserId, $enablePermissionCheck);
		try
		{
			$entityData = $merger->prepareEntityMergeData($seedEntityIds, $targEntityId);
			$this->prepareEditorDataModel($entityTypeId, $targEntityId, $entityData);
			return $entityData;
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			$errorMessage = $e->getLocalizedMessage();
		}
		catch(\Exception $e)
		{
			$errorMessage = $e->getMessage();
		}

		$this->addError(new Main\Error($errorMessage));
		return null;
	}
	public function prepareFieldMergeDataAction($entityTypeName, array $seedEntityIds, $targEntityId, $fieldId, array $options)
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$this->addError(new Main\Error('Entity Type is not specified or invalid.'));
			return false;
		}

		$seedEntityIds = array_map("intval", $seedEntityIds);
		if(empty($seedEntityIds))
		{
			$this->addError(new Main\Error('The parameter seedEntityIds is required.'));
			return null;
		}

		$targEntityId = (int)$targEntityId;
		if($targEntityId <= 0)
		{
			$this->addError(new Main\Error('The parameter targEntityId is required.'));
			return null;
		}

		if($fieldId === '')
		{
			$this->addError(new Main\Error('The parameter fieldId is required.'));
			return null;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->currentUserId);
		$merger = Crm\Merger\EntityMerger::create($entityTypeId, $this->currentUserId, $enablePermissionCheck);
		try
		{
			$entityData = [ $fieldId => $merger->prepareEntityFieldMergeData($fieldId, $seedEntityIds, $targEntityId, $options) ];
			$this->prepareEditorDataModel($entityTypeId, $targEntityId, $entityData);
			return $entityData;
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			$errorMessage = $e->getLocalizedMessage();
		}
		catch(\Exception $e)
		{
			$errorMessage = $e->getMessage();
		}

		$this->addError(new Main\Error($errorMessage));
		return null;
	}
	protected function prepareEditorDataModel($entityTypeId, $entityId, array &$fieldData)
	{
		foreach($fieldData as $key => $fieldInfo)
		{
			if(!isset($fieldData[$key]['VALUE']))
			{
				unset($fieldData[$key]);
				continue;
			}

			$type = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : '';
			$isMultiple = isset($fieldInfo['IS_MULTIPLE']) && $fieldInfo['IS_MULTIPLE'];
			if($type === 'crm_company' || $type === 'crm_contact')
			{
				$entityInfos = array();
				$entityIDs = $isMultiple && is_array($fieldInfo['VALUE']) ? $fieldInfo['VALUE'] : array($fieldInfo['VALUE']);
				$entityTypeID = $type === 'crm_company' ? CCrmOwnerType::Company : CCrmOwnerType::Contact;
				foreach($entityIDs as $entityID)
				{
					$entityInfos[] = Crm\Entity\EntityEditor::prepareEntityInfo(
						$entityTypeID,
						$entityID,
						array('USER_PERMISSIONS' => $this->currentUserPermissions)
					);
				}

				if(isset($fieldData[$key]['EXTRAS']))
				{
					$fieldData[$key]['EXTRAS'] = array();
				}
				$fieldData[$key]['EXTRAS']['INFOS'] = $entityInfos;
			}
		}

		$multiFieldTypeIds = array_keys(\CCrmFieldMulti::GetEntityTypeInfos());
		foreach($multiFieldTypeIds as $multiFieldTypeId)
		{
			if(!isset($fieldData[$multiFieldTypeId]))
			{
				continue;
			}

			Crm\Entity\EntityEditor::prepareMultiFieldDataModel(
				$entityTypeId,
				$entityId,
				$multiFieldTypeId,
				$fieldData[$multiFieldTypeId]['VALUE']
			);
		}
	}
}