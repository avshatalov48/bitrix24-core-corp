<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class MergeAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.processMerge", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class ProcessMergeAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		if(!Crm\Security\EntityAuthorization::isAuthorized())
		{
			$this->addError(new Main\Error('Access denied.'));
			return null;
		}

		$hash = isset($params['hash']) ? $params['hash'] : '';
		if($hash === '')
		{
			$this->addError(new Main\Error('The parameter hash is required.'));
			return null;
		}

		$data = isset($_SESSION['CRM_ENTITY_MERGE_DATA'][$hash])
			? $_SESSION['CRM_ENTITY_MERGE_DATA'][$hash] : null;

		if(!is_array($data))
		{
			return ['status' => 'COMPLETED', 'processedItems' => 0, 'totalItems' => 0 ];
		}

		$hash = isset($data['HASH']) ? $data['HASH'] : '';
		$gridID = isset($data['GRID_ID']) ? $data['GRID_ID'] : '';
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$entityIDs = isset($data['ENTITY_IDS']) && is_array($data['ENTITY_IDS']) ? $data['ENTITY_IDS'] : null;

		if(!isset($_SESSION['CRM_ENTITY_MERGE_PROGRESS']))
		{
			$_SESSION['CRM_ENTITY_MERGE_PROGRESS'] = [];
		}

		$progressData = isset($_SESSION['CRM_ENTITY_MERGE_PROGRESS'][$hash])
			? $_SESSION['CRM_ENTITY_MERGE_PROGRESS'][$hash] : null;

		if(!is_array($progressData))
		{
			$progressData = [
				'HASH' => $hash,
				'GRID_ID' => $gridID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'CURRENT_ENTITY_INDEX' => 0,
				'PROCESSED_COUNT' => 0
			];
		}

		$currentEntityIndex = isset($progressData['CURRENT_ENTITY_INDEX'])
			? (int)$progressData['CURRENT_ENTITY_INDEX'] : 0;

		$totalCount = count($entityIDs);
		if($currentEntityIndex < 0)
		{
			$currentEntityIndex = 0;
		}

		$errors = [];

		//TODO: Remove Criterion stub
		$typeID = Crm\Integrity\DuplicateIndexType::PERSON;
		$matches =  array('LAST_NAME' => '', 'NAME' => '', 'SECOND_NAME' => '');
		$criterion = Crm\Integrity\DuplicateManager::createCriterion($typeID, $matches);

		$currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		$enablePermissionCheck = !\CCrmPerms::IsAdmin($currentUserID);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);
		$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::ASK_USER);

		//TODO: Resolve Root entity through API
		$rootEntityID = $entityIDs[0];
		if($currentEntityIndex <= 0)
		{
			$currentEntityIndex = 1;
		}

		if($currentEntityIndex < $totalCount)
		{
			$errorMessage = '';
			$currentEntityID = $entityIDs[$currentEntityIndex];

			try
			{
				$merger->merge($currentEntityID, $rootEntityID, $criterion);
			}
			catch(Crm\Merger\EntityMergerException $e)
			{
				$errorMessage = $e->getLocalizedMessage();
			}
			catch(\Exception $e)
			{
				$errorMessage = $e->getMessage();
			}

			if($errorMessage !== '')
			{
				\CCrmOwnerType::TryGetEntityInfo(
					$entityTypeID,
					$currentEntityID,
					$entityInfo,
					false
				);
				$errors[] = new Main\Error(
					$errorMessage,
					0,
					[ 'info' => [ 'title' => $entityInfo['TITLE'], 'showUrl' => $entityInfo['SHOW_URL'] ] ]
				);
			}

			$currentEntityIndex++;
		}

		$progressData['PROCESSED_COUNT'] = $currentEntityIndex;
		$progressData['CURRENT_ENTITY_INDEX'] = $currentEntityIndex;

		$result = [
			'status' => ($currentEntityIndex < $totalCount) ? 'PROGRESS' : 'COMPLETED',
			'processedItems' => $currentEntityIndex,
			'totalItems' => $totalCount
		];

		if(!empty($errors))
		{
			$result['errors'] = $errors;
		}

		if(is_array($result) && isset($result['STATUS']) && $result['STATUS'] === 'COMPLETED')
		{
			unset(
				$_SESSION['CRM_ENTITY_MERGE_DATA'][$hash],
				$_SESSION['CRM_ENTITY_MERGE_PROGRESS'][$hash]
			);
		}
		else
		{
			$_SESSION['CRM_ENTITY_MERGE_PROGRESS'][$hash] = $progressData;
		}

		return $result;
	}
}