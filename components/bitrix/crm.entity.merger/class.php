<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("crm");
use Bitrix\Main;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

class CCrmEntityMergerComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var int */
	protected $entityTypeID = CCrmOwnerType::Undefined;
	/** @var string */
	protected $entityTypeName = '';
	/** @var int[] */
	protected $entityIDs = array();
	/** @var array */
	protected $dedupeCriterionData = array();
	/** @var string */
	protected $queueName = '';
	/** @var array */
	protected $queueData = array();

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function setEntityTypeID($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
	}

	public function executeComponent()
	{
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'entity_merger';
		$this->entityTypeID = $this->arResult['ENTITY_TYPE_ID'] = isset($this->arParams['ENTITY_TYPE_ID'])
			? (int)$this->arParams['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;

		$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		$this->entityIDs = $this->arResult['ENTITY_IDS'] = isset($this->arParams['ENTITY_IDS']) && is_array($this->arParams['ENTITY_IDS'])
			? $this->arParams['ENTITY_IDS'] : array();

		if (!empty($this->entityIDs) && $this->entityTypeID === \CCrmOwnerType::Deal)
		{
			$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
			if (!$restriction->hasPermission())
			{
				$restrictedItemIds = $restriction->filterRestrictedItemIds(
					$this->entityTypeID,
					$this->entityIDs
				);
				$this->entityIDs = array_diff($this->entityIDs, $restrictedItemIds);
			}
		}

		$this->arResult['PATH_TO_EDITOR'] = isset($this->arParams['PATH_TO_EDITOR'])
			? $this->arParams['PATH_TO_EDITOR'] : '';

		$this->arResult['HEADER_TEMPLATE'] = isset($this->arParams['HEADER_TEMPLATE'])
			? $this->arParams['HEADER_TEMPLATE'] : '';

		$this->arResult['RESULT_LEGEND'] = isset($this->arParams['RESULT_LEGEND'])
			? $this->arParams['RESULT_LEGEND'] : '';

		//region Deduplication
		$this->arResult['PATH_TO_DEDUPE_LIST'] = isset($this->arParams['~PATH_TO_DEDUPE_LIST'])
			? $this->arParams['~PATH_TO_DEDUPE_LIST'] : '';

		$this->arResult['PATH_TO_ENTITY_LIST'] = isset($this->arParams['~PATH_TO_ENTITY_LIST'])
			? $this->arParams['~PATH_TO_ENTITY_LIST'] : '';

		$this->arResult['IS_AUTOMATIC'] = ($this->request->get('is_automatic') === 'yes');

		$this->arResult['DEDUPE_CONFIG'] = [];
		$autosearchSettings = null;
		if ($this->arResult['IS_AUTOMATIC'])
		{
			$autosearchSettings = Crm\Integrity\AutoSearchUserSettings::getForUserByEntityType($this->entityTypeID, $this->userID);
			if ($autosearchSettings && $autosearchSettings->getStatusId() === Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING)
			{
				$this->arResult['DEDUPE_CONFIG']['typeNames'] = [];
				$progressData = $autosearchSettings->getProgressData();
				foreach ($progressData['TYPE_IDS'] as $typeId)
				{
					$this->arResult['DEDUPE_CONFIG']['typeNames'][] = Crm\Integrity\DuplicateIndexType::resolveName($typeId);
				}
				$this->arResult['DEDUPE_CONFIG']['scope'] = $progressData['CURRENT_SCOPE'];
			}
		}
		else
		{
			$typeNames = $this->request->get('typeNames');
			$typeNames = is_string($typeNames) ? explode(',', $typeNames) : [];
			if (!empty($typeNames))
			{
				$this->arResult['DEDUPE_CONFIG']['typeNames'] = $typeNames;

				$scope = $this->request->get('scope');
				$this->arResult['DEDUPE_CONFIG']['scope'] = is_string($scope) ? $scope : '';
			}
			//endregion
		}

		$this->arResult['DEDUPE_QUEUE_INFO'] = [ 'offset' => 0, 'length' => 0 ];
		if(isset($this->arResult['DEDUPE_CONFIG']) && !empty($this->arResult['DEDUPE_CONFIG']['typeNames']))
		{
			$typeIDs = array();
			foreach($this->arResult['DEDUPE_CONFIG']['typeNames'] as $typeName)
			{
				$typeID = Crm\Integrity\DuplicateIndexType::resolveID($typeName);
				if($typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED)
				{
					$typeIDs[] = $typeID;
				}
			}

			if(!empty($typeIDs))
			{
				$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->userID);
				$list = \Bitrix\Crm\Integrity\DuplicateListFactory::create(
					$this->arResult['IS_AUTOMATIC'],
					Crm\Integrity\DuplicateIndexType::joinType($typeIDs),
					$this->entityTypeID,
					$this->userID,
					$enablePermissionCheck
				);

				$list->setScope(
					isset($this->arResult['DEDUPE_CONFIG']['scope'])
						? $this->arResult['DEDUPE_CONFIG']['scope'] : ''
				);
				$list->setStatusIDs([
					Crm\Integrity\DuplicateStatus::CONFLICT,
					Crm\Integrity\DuplicateStatus::POSTPONED,
					Crm\Integrity\DuplicateStatus::ERROR
				]);

				$this->arResult['DEDUPE_QUEUE_INFO']['length'] = $list->getRootItemCount();

				if($this->arResult['DEDUPE_QUEUE_INFO']['length'] > 0)
				{
					$list->enableNaturalSort(true);

					$this->extractEntityIdsFormList($list, 0, $this->entityIDs, $this->dedupeCriterionData);

					if ($this->arResult['IS_AUTOMATIC'] && $autosearchSettings)
					{
						$progressData = $autosearchSettings->getProgressData();
						$successCount = (int)$progressData['MERGE_DATA']['SUCCESS'];
						$conflictCount = (int)$progressData['MERGE_DATA']['CONFLICT'];
						$errorCount = (int)$progressData['MERGE_DATA']['ERROR'];

						$successCount += max(0, $conflictCount + $errorCount - $this->arResult['DEDUPE_QUEUE_INFO']['length']);

						$this->arResult['PROCESSED_COUNT'] = $successCount;
					}
				}

				if (!$this->arResult['DEDUPE_QUEUE_INFO']['length'] && $this->arResult['IS_AUTOMATIC'] &&
					$autosearchSettings && $autosearchSettings->getStatusId() === Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING)
				{
					$autosearchSettings
						->setStatusId(Crm\Integrity\AutoSearchUserSettings::STATUS_NEW)
						->save();
				}
			}
		}

		$queueName = $this->request->get('queue');
		if(is_string($queueName) && $queueName !== '')
		{
			$this->queueName = $queueName;
		}
		else
		{
			$this->queueName = isset($this->arParams['QUEUE_NAME'])
				? $this->arParams['QUEUE_NAME'] : '';
		}

		$this->arResult['QUEUE_NAME'] = $this->queueName;
		$this->arResult['QUEUE_DATA'] = array();

		if($this->queueName !== '')
		{
			$this->queueData = $this->arResult['QUEUE_DATA'] = CUserOptions::GetOption('crm', $this->queueName, array(), $this->userID);
			if(isset($this->queueData['ITEMS']) && is_array($this->queueData['ITEMS']) && !empty($this->queueData['ITEMS']))
			{
				$queueItem = $this->queueData['ITEMS'][0];
				if(is_array($queueItem))
				{
					$entityIDs = isset($queueItem['ENTITY_IDS']) && is_array($queueItem['ENTITY_IDS']) ? $queueItem['ENTITY_IDS'] : array();
					$rootEntityID =  isset($queueItem['ROOT_ENTITY_ID']) && $queueItem['ROOT_ENTITY_ID'] > 0 ? (int)$queueItem['ROOT_ENTITY_ID'] : 0;
					if($rootEntityID > 0)
					{
						$entityIDs = array_merge(array($rootEntityID), $entityIDs);
					}

					if(!empty($entityIDs))
					{
						$this->entityIDs = $entityIDs;
					}
				}
			}
		}

		$this->arResult['ENTITY_IDS'] = $this->entityIDs;
		$this->prepareEntityInfos();

		$this->arResult['DEDUPE_CRITERION_DATA'] = $this->dedupeCriterionData;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			\CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['ENTITY_EDITOR_CONFIGURATION_ID'] = 'merger_'.mb_strtolower($this->entityTypeName);

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('externalContextId');
		if(!is_string($this->arResult['EXTERNAL_CONTEXT_ID']))
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
		}

		$this->includeComponentTemplate();
	}

	public function prepareEntityInfos()
	{
		$this->arResult['ENTITY_INFOS'] = array();
		foreach($this->entityIDs as $entityID)
		{
			$this->arResult['ENTITY_INFOS'][] = array(
				'ENTITY_ID' => $entityID,
				'DETAILS_URL' => \CCrmOwnerType::GetDetailsUrl(
					$this->entityTypeID,
					$entityID,
					true,
					array()
				)
			);
		}
		return $this->arResult['ENTITY_INFOS'];
	}

	public function prepareDedupeData($typeNames, $scope, $offset, bool $isAutomatic = false)
	{
		$this->arResult['DEDUPE_CONFIG'] = [];
		if(!empty($typeNames))
		{
			$this->arResult['DEDUPE_CONFIG']['typeNames'] = $typeNames;
			$this->arResult['DEDUPE_CONFIG']['scope'] = $scope;
		}

		$typeIDs = array();
		foreach($this->arResult['DEDUPE_CONFIG']['typeNames'] as $typeName)
		{
			$typeID = Crm\Integrity\DuplicateIndexType::resolveID($typeName);
			if($typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED)
			{
				$typeIDs[] = $typeID;
			}
		}

		$list = $this->getDedupeQueueList($typeIDs, $scope, $isAutomatic);
		$this->arResult['DEDUPE_QUEUE_INFO'] = [ 'offset' => $offset, 'length' => $list->getRootItemCount() ];
		if($this->arResult['DEDUPE_QUEUE_INFO']['length'] > 0)
		{
			$list->setSortTypeID($this->entityTypeID === \CCrmOwnerType::Company
				? Crm\Integrity\DuplicateIndexType::ORGANIZATION
				: Crm\Integrity\DuplicateIndexType::PERSON
			);
			$list->setSortOrder(SORT_ASC);

			$this->extractEntityIdsFormList($list, $offset, $this->entityIDs, $this->dedupeCriterionData);

			$this->arResult['ENTITY_IDS'] = $this->entityIDs;
			$this->arResult['DEDUPE_CRITERION_DATA'] = $this->dedupeCriterionData;
		}

		if (!$this->arResult['DEDUPE_QUEUE_INFO']['length'] && $isAutomatic)
		{
			$autosearchSettings = Crm\Integrity\AutoSearchUserSettings::getForUserByEntityType($this->entityTypeID, $this->userID);

			if($autosearchSettings->getStatusId() === Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING)
			{
				$autosearchSettings
					->setStatusId(Crm\Integrity\AutoSearchUserSettings::STATUS_NEW)
					->save();
			}
		}
	}

	public function getDedupeQueueList(array $typeIDs, $scope, bool $isAutomatic = false)
	{
		if(empty($typeIDs))
		{
			return null;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->userID);
		$list = \Bitrix\Crm\Integrity\DuplicateListFactory::create(
			$isAutomatic,
			Crm\Integrity\DuplicateIndexType::joinType($typeIDs),
			$this->entityTypeID,
			$this->userID,
			$enablePermissionCheck
		);

		$list->setScope($scope);
		$list->setStatusIDs([
			Crm\Integrity\DuplicateStatus::CONFLICT,
			Crm\Integrity\DuplicateStatus::POSTPONED,
			Crm\Integrity\DuplicateStatus::ERROR
		]);

		return $list;
	}

	protected function extractEntityIdsFormList($list, $offset, &$entityIds, &$dedupeCriterionData)
	{
		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->userID);

		$iterations = 0;
		while ($iterations++ < 50)
		{
			$items = $list->getRootItems($offset, 1);
			if (empty($items))
			{
				break;
			}
			$item = $items[0];
			$rootEntityID = $item->getRootEntityID();
			$criterion = $item->getCriterion();
			$criterionEntityIDs = $criterion->getEntityIDs(
				$this->entityTypeID,
				$rootEntityID,
				$this->userID,
				$enablePermissionCheck,
				['limit' => 50]
			);
			if (count($criterionEntityIDs))
			{
				$rootEntityInfo = [
					$rootEntityID => []
				];
				\CCrmOwnerType::PrepareEntityInfoBatch(
					$this->entityTypeID,
					$rootEntityInfo,
					$enablePermissionCheck,
					[
						'ENABLE_EDIT_URL' => false,
						'ENABLE_RESPONSIBLE' => false,
						'ENABLE_RESPONSIBLE_PHOTO' => false
					]
				);
				$rootEntityExists = !empty($rootEntityInfo[$rootEntityID]);
				if ($rootEntityExists || count($criterionEntityIDs) > 1)
				{
					$entityIds = $criterionEntityIDs;
					if ($rootEntityExists)
					{
						$entityIds = array_merge([$rootEntityID], $entityIds);
					}
					$dedupeCriterionData = [
						'matches' => $criterion->getMatches(),
						'typeId' => $criterion->getIndexTypeID(),
						'queueId' => $item->getQueueId()
					];
					break;
				}
			}

			//Skip Junk item
			Crm\Integrity\DuplicateManager::deleteDuplicateIndexItems(
				[
					'USER_ID' => $this->userID,
					'ENTITY_TYPE_ID' => $this->entityTypeID,
					'TYPE_ID' => $criterion->getIndexTypeID(),
					'MATCH_HASH' => $criterion->getMatchHash()
				],
				$list->isAutomatic()
			);
			// Recalculate actual queue length
			$this->arResult['DEDUPE_QUEUE_INFO']['length'] = $list->getRootItemCount();
		}
	}
}