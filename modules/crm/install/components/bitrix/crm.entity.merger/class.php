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

		$this->arResult['PATH_TO_EDITOR'] = isset($this->arParams['PATH_TO_EDITOR'])
			? $this->arParams['PATH_TO_EDITOR'] : '';

		$this->arResult['HEADER_TEMPLATE'] = isset($this->arParams['HEADER_TEMPLATE'])
			? $this->arParams['HEADER_TEMPLATE'] : '';

		$this->arResult['RESULT_LEGEND'] = isset($this->arParams['RESULT_LEGEND'])
			? $this->arParams['RESULT_LEGEND'] : '';

		//region Deduplication
		$this->arResult['PATH_TO_DEDUPE_LIST'] = isset($this->arParams['~PATH_TO_DEDUPE_LIST'])
			? $this->arParams['~PATH_TO_DEDUPE_LIST'] : '';

		$this->arResult['DEDUPE_CONFIG'] = [];
		$typeNames = $this->request->get('typeNames');
		$typeNames = is_string($typeNames) ? explode(',', $typeNames) : [];
		if(!empty($typeNames))
		{
			$this->arResult['DEDUPE_CONFIG']['typeNames'] = $typeNames;

			$scope = $this->request->get('scope');
			$this->arResult['DEDUPE_CONFIG']['scope'] = is_string($scope) ? $scope : '';
		}
		//endregion

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
				$list = new Crm\Integrity\DuplicateList(
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
					$list->setSortTypeID($this->entityTypeID === \CCrmOwnerType::Company
						? Crm\Integrity\DuplicateIndexType::ORGANIZATION
						: Crm\Integrity\DuplicateIndexType::PERSON
					);
					$list->setSortOrder(SORT_ASC);

					$items = $list->getRootItems(0, 1);
					if(!empty($items))
					{
						$item = $items[0];
						$rootEntityID = $item->getRootEntityID();
						$criterion = $item->getCriterion();
						$entityIDs = $criterion->getEntityIDs(
							$this->entityTypeID,
							$rootEntityID,
							$this->userID,
							$enablePermissionCheck,
							['limit' => 50]
						);

						$this->entityIDs = array_merge([ $rootEntityID ], $entityIDs);
						$this->dedupeCriterionData = [
							'matches' => $criterion->getMatches(),
							'typeId' => $criterion->getIndexTypeID()
						];
					}
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

	public function prepareDedupeData($typeNames, $scope, $offset)
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

		$list = $this->getDedupeQueueList($typeIDs, $scope);
		$this->arResult['DEDUPE_QUEUE_INFO'] = [ 'offset' => $offset, 'length' => $list->getRootItemCount() ];
		if($this->arResult['DEDUPE_QUEUE_INFO']['length'] > 0)
		{
			$list->setSortTypeID($this->entityTypeID === \CCrmOwnerType::Company
				? Crm\Integrity\DuplicateIndexType::ORGANIZATION
				: Crm\Integrity\DuplicateIndexType::PERSON
			);
			$list->setSortOrder(SORT_ASC);

			$items = $list->getRootItems($offset, 1);
			if(!empty($items))
			{
				$item = $items[0];
				$rootEntityID = $item->getRootEntityID();
				$criterion = $item->getCriterion();
				$entityIDs = $criterion->getEntityIDs(
					$this->entityTypeID,
					$rootEntityID,
					$this->userID,
					!\CCrmPerms::IsAdmin($this->userID),
					['limit' => 50]
				);

				$this->entityIDs = array_merge([ $rootEntityID ], $entityIDs);
				$this->dedupeCriterionData = [
					'matches' => $criterion->getMatches(),
					'typeId' => $criterion->getIndexTypeID()
				];
			}

			$this->arResult['ENTITY_IDS'] = $this->entityIDs;
			$this->arResult['DEDUPE_CRITERION_DATA'] = $this->dedupeCriterionData;
		}
	}

	public function getDedupeQueueList(array $typeIDs, $scope)
	{
		if(empty($typeIDs))
		{
			return null;
		}

		$enablePermissionCheck = !\CCrmPerms::IsAdmin($this->userID);
		$list = new Crm\Integrity\DuplicateList(
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
}