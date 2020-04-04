<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.processMergeByMap", { data: { params: { entityTypeId: 3, seedEntityIds: {100, 101}, targEntityId: 99, map: { "TYPE_ID" => { "SOURCE_ENTITY_IDS" => [100] } } } } });
 */
class ProcessMergeByMapAction extends Main\Engine\Action
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

		$seedEntityIds = isset($params['seedEntityIds']) && is_array($params['seedEntityIds']) ? $params['seedEntityIds'] : null;
		if(!is_array($seedEntityIds) || empty($seedEntityIds))
		{
			$this->addError(new Main\Error('The parameter seedEntityIds is required.'));
			return null;
		}

		$targEntityID = isset($params['targEntityId']) ? (int)$params['targEntityId'] : 0;
		if($targEntityID <= 0)
		{
			$this->addError(new Main\Error('The parameter targEntityId is required.'));
			return null;
		}

		$map = isset($params['map']) && is_array($params['map']) ? $params['map'] : null;

		$currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		$enablePermissionCheck = !\CCrmPerms::IsAdmin($currentUserID);
		$merger = Crm\Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);
		$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::ASK_USER);
		if($map !== null)
		{
			$merger->setMap($map);
		}
		try
		{
			$merger->mergeBatch($seedEntityIds, $targEntityID);
			return null;
		}
		catch(Crm\Merger\EntityMergerException $e)
		{
			$errorMessage = $e->getLocalizedMessage();
		}
		catch(\Exception $e)
		{
			$errorMessage = $e->getMessage();
		}

		return [
			'errors' => [
				new Main\Error(
					$errorMessage,
					0,
					[]
				)
			]
		];
	}
}