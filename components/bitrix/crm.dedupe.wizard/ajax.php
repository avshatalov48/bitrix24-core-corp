<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Integrity\DuplicateIndexBuilder;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmDedupeWizardComponentAjaxController extends Main\Engine\Controller
{
	protected $currentUser = null;
	protected $currentUserID = 0;
	protected $currentUserPermissions = null;

	protected function getHelper(): CCrmDedupeWizardComponentHelper
	{
		static $helper = null;

		if ($helper === null)
		{
			include_once(Main\IO\Path::normalize('helper.php'));
			$helper = CCrmDedupeWizardComponentHelper::getInstance();
		}

		return $helper;
	}

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		CModule::IncludeModule('crm');

		$this->currentUser = CCrmSecurityHelper::GetCurrentUser();
		$this->currentUserID = (int)$this->currentUser->GetID();
		$this->currentUserPermissions = CCrmPerms::GetUserPermissions($this->currentUserID);

		return parent::processBeforeAction($action);
	}

	public function configureActions()
	{
		return [
			'getSettingsSliderContent' => [
				'-prefilters' => [
					Bitrix\Main\Engine\ActionFilter\Csrf::class,
				],
			],
		];
	}

	public function getSettingsSliderContentAction(string $entityTypeId, string $guid): HttpResponse
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:crm.dedupe.settings',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'ENTITY_TYPE_ID' => $entityTypeId,
					'GUID' => $guid
				],
				'BUTTONS' => ['save', 'cancel'],
				'USE_PADDING' =>false,
				'IFRAME_MODE' => true
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	public function rebuildIndexAction($contextId, $entityTypeName, array $types, $scope)
	{
		if($contextId === '')
		{
			$this->addError(new \Bitrix\Main\Error('Context ID is not defined.'));
			return false;
		}

		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new \Bitrix\Main\Error('Entity Type Name is not defined or invalid.'));
			return false;
		}

		$typeIDs = array();
		foreach($types as $typeName)
		{
			$typeID = Crm\Integrity\DuplicateIndexType::resolveID($typeName);
			if($typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED)
			{
				$typeIDs[] = $typeID;
			}
		}

		if(empty($typeIDs))
		{
			$this->addError(new \Bitrix\Main\Error('Index types are not defined or invalid.'));
			return false;
		}

		if (!Crm\Integrity\DuplicateIndexType::checkScopeValue($scope))
		{
			$this->addError(new \Bitrix\Main\Error('Scope is invalid.'));
			return false;
		}

		$progressData = CUserOptions::GetOption('crm', '~dedupe_index_rebuild_progress', array(), $this->currentUserID);
		if(!empty($progressData)
			&& (!isset($progressData['CONTEXT_ID']) || $progressData['CONTEXT_ID'] !== $contextId))
		{
			$progressData = array();
		}

		$isStart = (empty($progressData) || !isset($progressData['CONTEXT_ID']));
		if($isStart)
		{
			$progressData['CONTEXT_ID'] = $contextId;

			$effectiveTypeIDs = $progressData['TYPE_IDS'] = $typeIDs;
			$effectiveScope = $progressData['CURRENT_SCOPE'] = $scope;
			$currentTypeIndex = $progressData['CURRENT_TYPE_INDEX'] = 0;
			$progressData['PROCESSED_ITEMS'] = 0;
			$progressData['FOUND_ITEMS'] = 0;

			$totalItemQty = 0;
			foreach($typeIDs as $typeID)
			{
				$builder = Crm\Integrity\DuplicateManager::createIndexBuilder(
					$typeID,
					$entityTypeID,
					$this->currentUserID,
					!Container::getInstance()->getUserPermissions($this->currentUserID)->isAdmin(),
					array('SCOPE' => $effectiveScope)
				);
				$totalItemQty += $builder->getTotalCount();
			}
			$progressData['TOTAL_ITEMS'] = $totalItemQty;

			CUserOptions::DeleteOption('crm', mb_strtolower($entityTypeName).'_dedupe_queue', false, $this->currentUserID);
		}
		else
		{
			$effectiveTypeIDs = $progressData['TYPE_IDS'] ?? null;
			if(!is_array($effectiveTypeIDs) || empty($effectiveTypeIDs))
			{
				$effectiveTypeIDs = $typeIDs;
			}
			$effectiveScope = $progressData['CURRENT_SCOPE'] ?? Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE;
			$currentTypeIndex = (int)($progressData['CURRENT_TYPE_INDEX'] ?? 0);
		}

		$effectiveTypeQty = count($effectiveTypeIDs);
		if($currentTypeIndex >= $effectiveTypeQty)
		{
			__CrmDedupeListEndResponse(array('ERROR' => 'Invalid current type index.'));
		}

		$builder = Crm\Integrity\DuplicateManager::createIndexBuilder(
			$effectiveTypeIDs[$currentTypeIndex],
			$entityTypeID,
			$this->currentUserID,
			!Container::getInstance()->getUserPermissions($this->currentUserID)->isAdmin(),
			array('SCOPE' => $effectiveScope)
		);

		$buildData = $progressData['BUILD_DATA'] ?? [];

		$offset = isset($buildData['OFFSET']) ? (int)$buildData['OFFSET'] : 0;
		if($offset === 0)
		{
			$builder->remove();
		}

		$limit = isset($buildData['LIMIT']) ? (int)$buildData['LIMIT'] : 0;
		if($limit === 0)
		{
			$buildData['LIMIT'] = 10;
		}

		$isInProgress = $builder->build($buildData);
		if(isset($buildData['PROCESSED_ITEM_COUNT']))
		{
			$progressData['PROCESSED_ITEMS'] += $buildData['PROCESSED_ITEM_COUNT'];
		}

		if(isset($buildData['EFFECTIVE_ITEM_COUNT']))
		{
			$progressData['FOUND_ITEMS'] += $buildData['EFFECTIVE_ITEM_COUNT'];
		}

		$progressData['BUILD_DATA'] = $buildData;

		$isFinal = false;
		if(!$isInProgress)
		{
			$isFinal = $currentTypeIndex === ($effectiveTypeQty - 1);
			if(!$isFinal)
			{
				$progressData['CURRENT_TYPE_INDEX'] = ++$currentTypeIndex;
				unset($progressData['BUILD_DATA']);
			}
		}

		if(!$isFinal)
		{
			CUserOptions::SetOption('crm', '~dedupe_index_rebuild_progress', $progressData, false, $this->currentUserID);
		}
		else
		{
			CUserOptions::DeleteOption('crm', '~dedupe_index_rebuild_progress', false, $this->currentUserID);
		}


		$totalEntities = 0;
		if($isFinal)
		{
			$totalEntities = Crm\Integrity\DuplicateList::getTotalEntityCount(
				$this->currentUserID,
				$entityTypeID,
				$effectiveTypeIDs,
				$effectiveScope
			);
		}

		return array(
			'STATUS' => !$isFinal ? 'PROGRESS' : 'COMPLETED',
			'FOUND_ITEMS' => $progressData['FOUND_ITEMS'],
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
			'TOTAL_ENTITIES' => $totalEntities
		);
	}

	public function rebuildIndexBackgroundAction($entityTypeName, array $types, string $scope, string $tryStart): array
	{
		$types = ($tryStart === 'Y') ? $types : [];
		return $this->getHelper()->getDuplicateIndexState($this->currentUserID, $entityTypeName, $types, $scope);
	}

	public function stopRebuildIndexBackgroundAction($entityTypeName): array
	{
		return $this->getHelper()->stopDuplicateIndex($this->currentUserID, $entityTypeName);
	}

	public function deleteRebuildIndexBackgroundAction($entityTypeName): bool
	{
		$this->getHelper()->getIndexAgent($this->currentUserID, $entityTypeName)->delete();

		return true;
	}

	public function mergeAction($entityTypeName, array $types, $scope, $mode = '')
	{
		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$this->addError(new Main\Error('Entity Type Name is not defined or invalid.'));
			return false;
		}

		$typeIDs = array();
		foreach($types as $typeName)
		{
			$typeID = Crm\Integrity\DuplicateIndexType::resolveID($typeName);
			if($typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED)
			{
				$typeIDs[] = $typeID;
			}
		}

		if(empty($typeIDs))
		{
			$this->addError(new Main\Error('Index types are not defined or invalid.'));
			return false;
		}

		$mode = (string)$mode;
		if ($mode == '')
		{
			$mode = 'auto';
		}
		$mergeModes = [
			'auto' => Crm\Merger\ConflictResolutionMode::ASK_USER,
			'manual' => Crm\Merger\ConflictResolutionMode::MANUAL
		];
		if (!isset($mergeModes[$mode]))
		{
			$this->addError(new Main\Error('Wrong merge mode.'));
			return false;
		}

		$enablePermissionCheck = !Container::getInstance()->getUserPermissions($this->currentUserID)->isAdmin();
		$list = new Crm\Integrity\DuplicateList(
			Crm\Integrity\DuplicateIndexType::joinType($typeIDs),
			$entityTypeID,
			$this->currentUserID,
			$enablePermissionCheck
		);

		if(!is_string($scope))
		{
			$scope = (string)$scope;
		}
		$list->setScope($scope);
		$list->setStatusIDs([ Crm\Integrity\DuplicateStatus::PENDING ]);

		$list->setSortTypeID($entityTypeID === CCrmOwnerType::Company
			? Crm\Integrity\DuplicateIndexType::ORGANIZATION
			: Crm\Integrity\DuplicateIndexType::PERSON
		);
		$list->setSortOrder(SORT_ASC);

		$items = $list->getRootItems(0, 1);
		if(empty($items))
		{
			$list->setStatusIDs([]);
			$totalItems = $list->getRootItemCount();
			$totalEntities = Crm\Integrity\DuplicateList::getTotalEntityCount(
				$this->currentUserID,
				$entityTypeID,
				$typeIDs,
				$scope
			);
			return [ 'STATUS' => 'COMPLETED', 'TOTAL_ITEMS' => $totalItems, 'TOTAL_ENTITIES' => $totalEntities ];
		}

		$item = $items[0];

		$rootEntityID = $item->getRootEntityID();
		$criterion = $item->getCriterion();
		$entityIDs = $criterion->getEntityIDs($entityTypeID, $rootEntityID, $this->currentUserID, $enablePermissionCheck, ['limit' => 50]);
		$entityIDs = array_unique($entityIDs);
		if(empty($entityIDs))
		{
			//Skip Junk item
			Crm\Integrity\Entity\DuplicateIndexTable::deleteByFilter(
				array(
					'USER_ID' => $this->currentUserID,
					'ENTITY_TYPE_ID' => $entityTypeID,
					'TYPE_ID' => $criterion->getIndexTypeID(),
					'MATCH_HASH' => $criterion->getMatchHash()
				)
			);
			return ['STATUS' => 'SUCCESS'];
		}

		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $this->currentUserID, $enablePermissionCheck);
		$merger->setConflictResolutionMode($mergeModes[$mode]);
		$result = ['STATUS' => 'SUCCESS'];
		try
		{
			$merger->mergeBatch($entityIDs, $rootEntityID, $criterion);
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			if($e->getCode() === Crm\Merger\EntityMergerException::CONFLICT_OCCURRED)
			{
				$result['STATUS'] = 'CONFLICT';

				DuplicateIndexBuilder::setStatusID(
					$this->currentUserID,
					$entityTypeID,
					$criterion->getIndexTypeID(),
					$criterion->getMatchHash(),
					$scope,
					Crm\Integrity\DuplicateStatus::CONFLICT
				);
			}
			else
			{
				$result['STATUS'] = 'ERROR';
				$result['MESSAGE'] = $e->getLocalizedMessage();

				DuplicateIndexBuilder::setStatusID(
					$this->currentUserID,
					$entityTypeID,
					$criterion->getIndexTypeID(),
					$criterion->getMatchHash(),
					$scope,
					Crm\Integrity\DuplicateStatus::ERROR
				);

				return $result;
			}
		}
		catch(Exception $e)
		{
			$this->addError(new Main\Error($e->getMessage()));
			return false;
		}
		return $result;
	}

	public function mergeBackgroundAction($entityTypeName, array $types, $scope, string $tryStart): array
	{
		if ($tryStart === 'Y')
		{
			$this->getHelper()->getMergeAgent($this->currentUserID, $entityTypeName)->start($types, $scope);
		}

		return $this->getHelper()->getMergeAgentState($this->currentUserID, $entityTypeName);
	}

	public function stopMergeBackgroundAction($entityTypeName): array
	{
		$this->getHelper()->getMergeAgent($this->currentUserID, $entityTypeName)->stop();

		return $this->getHelper()->getMergeAgentState($this->currentUserID, $entityTypeName);
	}

	public function deleteMergeBackgroundAction($entityTypeName): bool
	{
		$this->getHelper()->getMergeAgent($this->currentUserID, $entityTypeName)->delete();

		return true;
	}
}
