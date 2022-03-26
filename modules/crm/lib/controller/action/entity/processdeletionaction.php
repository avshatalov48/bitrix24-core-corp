<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.processDeletion", { data: { params: { hash: "fea80f2db003d4ebc4536023814aa885" } } });
 */
class ProcessDeletionAction extends Main\Engine\Action
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

		$data = isset($_SESSION['CRM_ENTITY_DELETION_DATA'][$hash])
			? $_SESSION['CRM_ENTITY_DELETION_DATA'][$hash] : null;

		if(!is_array($data))
		{
			return ['status' => 'COMPLETED', 'processedItems' => 0, 'totalItems' => 0 ];
		}

		$hash = isset($data['HASH']) ? $data['HASH'] : '';
		$gridID = isset($data['GRID_ID']) ? $data['GRID_ID'] : '';
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;

		if(!isset($_SESSION['CRM_ENTITY_DELETION_PROGRESS']))
		{
			$_SESSION['CRM_ENTITY_DELETION_PROGRESS'] = [];
		}

		$progressData = isset($_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash])
			? $_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash] : null;

		$entityIDs = isset($data['ENTITY_IDS']) && is_array($data['ENTITY_IDS']) ? $data['ENTITY_IDS'] : null;
		if(is_array($entityIDs))
		{
			$result = $this->deleteByIDs($hash, $gridID, $entityTypeID, $entityIDs, $progressData);
		}
		else
		{
			$filterFields = isset($data['FILTER']) && is_array($data['FILTER']) ? $data['FILTER'] : [];
			$result = self::deleteByFilter($hash, $gridID, $entityTypeID, $filterFields, $progressData);
		}

		if(is_array($result) && isset($result['STATUS']) && $result['STATUS'] === 'COMPLETED')
		{
			unset(
				$_SESSION['CRM_ENTITY_DELETION_DATA'][$hash],
				$_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash]
			);
		}
		else
		{
			$_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash] = $progressData;
		}

		return $result;
	}
	protected function deleteByIDs($hash, $gridID, $entityTypeID, array $entityIDs, array &$progressData = null)
	{
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
		$processedCount = isset($progressData['PROCESSED_COUNT'])
			? (int)$progressData['PROCESSED_COUNT'] : 0;

		$userPermissions = Crm\Security\EntityAuthorization::getUserPermissions(
			Crm\Security\EntityAuthorization::getCurrentUserID()
		);

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if($entity === null)
		{
			$this->addError(new Main\Error('Entity type is not supported in current context.'));
			return null;
		}

		$totalCount = count($entityIDs);
		if($currentEntityIndex < 0)
		{
			$currentEntityIndex = 0;
		}

		$errors = [];
		$connection = Main\Application::getConnection();
		for($i = 0; $i < 10; $i++)
		{
			if($currentEntityIndex >= $totalCount)
			{
				break;
			}

			$currentEntityID = $entityIDs[$currentEntityIndex];
			if($currentEntityID > 0 && $entity->checkDeletePermission($currentEntityID, $userPermissions))
			{
				$connection->startTransaction();
				$error = $entity->delete($currentEntityID);
				if(is_array($error))
				{
					$connection->rollbackTransaction();
					\CCrmOwnerType::TryGetEntityInfo(
						$entityTypeID,
						$currentEntityID,
						$entityInfo,
						false
					);

					$errors[] = new Main\Error(
						$error['MESSAGE'],
						0,
						[ 'info' => [ 'title' => $entityInfo['TITLE'], 'showUrl' => $entityInfo['SHOW_URL'] ] ]
					);
				}
				else
				{
					$connection->commitTransaction();
				}
			}

			$processedCount++;
			$currentEntityIndex++;
		}

		$progressData['PROCESSED_COUNT'] = $processedCount;
		$progressData['CURRENT_ENTITY_INDEX'] = $currentEntityIndex;

		$resultData = [
			'status' => ($processedCount < $totalCount) ? 'PROGRESS' : 'COMPLETED',
			'processedItems' => $processedCount,
			'totalItems' => $totalCount
		];

		if(!empty($errors))
		{
			$resultData['errors'] = $errors;
		}

		return $resultData;
	}
	protected static function deleteByFilter($hash, $gridID, $entityTypeID, array $filterFields, array &$progressData = null)
	{
		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!is_array($progressData))
		{
			$totalCount = $entity->getCount([ 'filter' => $filterFields ]);
			$progressData = [
				'HASH' => $hash,
				'GRID_ID' => $gridID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'CURRENT_ENTITY_ID' => 0,
				'PROCESSED_COUNT' => 0,
				'TOTAL_COUNT' => $totalCount
			];
		}

		$currentEntityID = isset($progressData['CURRENT_ENTITY_ID'])
			? (int)$progressData['CURRENT_ENTITY_ID'] : 0;
		$processedCount = isset($progressData['PROCESSED_COUNT'])
			? (int)$progressData['PROCESSED_COUNT'] : 0;
		$totalCount = isset($progressData['TOTAL_COUNT'])
			? (int)$progressData['TOTAL_COUNT'] : 0;

		$userPermissions = Crm\Security\EntityAuthorization::getUserPermissions(
			Crm\Security\EntityAuthorization::getCurrentUserID()
		);

		$entityFilter = $filterFields;
		if($currentEntityID > 0)
		{
			$entityFilter['>ID'] = $currentEntityID;
		}

		$entityIDs = $entity->getTopIDs([ 'order' => [ 'ID' => 'ASC' ], 'filter' => $entityFilter, 'limit' => 10 ]);

		$errors = [];
		if(!empty($entityIDs))
		{
			foreach($entityIDs as $entityID)
			{
				$currentEntityID = $entityID;
				if($entity->checkDeletePermission($currentEntityID, $userPermissions))
				{
					$error = $entity->delete($currentEntityID);
					if(is_array($error))
					{
						\CCrmOwnerType::TryGetEntityInfo(
							$entityTypeID,
							$currentEntityID,
							$entityInfo,
							false
						);

						$errors[] = new Main\Error(
							$error['MESSAGE'],
							0,
							[ 'info' => [ 'title' => $entityInfo['TITLE'], 'showUrl' => $entityInfo['SHOW_URL'] ] ]
						);
					}
				}
				$processedCount++;
			}
		}
		elseif($processedCount !== $totalCount)
		{
			$processedCount = $totalCount;
		}

		$progressData['PROCESSED_COUNT'] = $processedCount;
		$progressData['CURRENT_ENTITY_ID'] = $currentEntityID;

		$resultData =
			[
				'status' => ($processedCount < $totalCount) ? 'PROGRESS' : 'COMPLETED',
				'processedItems' => $processedCount,
				'totalItems' => $totalCount
			];

		if(!empty($errors))
		{
			$resultData['errors'] = $errors;
		}

		return $resultData;
	}
}