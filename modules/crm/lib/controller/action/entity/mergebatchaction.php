<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class MergeAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.mergeBatch", { data: { params: { gridId: "CONTACT_LIST", entityTypeId: 3, entityIds: [ 100, 101 ] } } });
 */
class MergeBatchAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		if(!Crm\Security\EntityAuthorization::isAuthorized())
		{
			$this->addError(new Main\Error('Access denied.'));
			return null;
		}

		$entityTypeID = isset($params['entityTypeId']) ? (int)$params['entityTypeId'] : \CCrmOwnerType::Undefined;
		if($entityTypeID === \CCrmOwnerType::Undefined)
		{
			$this->addError(new Main\Error('The parameter entityTypeId is required.'));
			return null;
		}

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);

		$entityIDs = isset($params['entityIds']) && is_array($params['entityIds']) ? $params['entityIds'] : null;
		$effectiveEntityIDs = [];

		foreach($entityIDs as $entityID)
		{
			if($entity->isExists($entityID))
			{
				$effectiveEntityIDs[] = $entityID;
			}
		}

		if(empty($effectiveEntityIDs))
		{
			$this->addError(new Main\Error('The parameter entityIds does not contains valid elements.'));
			return null;
		}

		if(count($effectiveEntityIDs) < 2)
		{
			$this->addError(new Main\Error('The parameter entityIds must contains at least two elements.'));
			return null;
		}

		//TODO: Resolve Root entity through API
		$rootEntityID = array_shift($effectiveEntityIDs);

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!$entity->isExists($rootEntityID))
		{
			return [ 'STATUS' => 'NOT_FOUND' ];
		}

		$currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		$enablePermissionCheck = !\CCrmPerms::IsAdmin($currentUserID);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);
		$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::ASK_USER);

		$result = [
			'STATUS' => 'SUCCESS',
			'ENTITY_IDS' => $effectiveEntityIDs
		];

		//TODO: Resolve Root entity through API
		try
		{
			$merger->mergeBatch($effectiveEntityIDs, $rootEntityID);
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			if($e->getCode() === Crm\Merger\EntityMergerException::CONFLICT_OCCURRED)
			{
				$result['STATUS'] = 'CONFLICT';
			}
			else
			{
				$result['STATUS'] = 'ERROR';
			}
			$this->addError(new Main\Error($e->getLocalizedMessage()));
		}
		catch(\Exception $e)
		{
			$result['STATUS'] = 'ERROR';
			$this->addError(new Main\Error($e->getMessage()));
		}
		return $result;
	}
}