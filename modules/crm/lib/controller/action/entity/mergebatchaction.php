<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\Item;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Service\Container;

/**
 * Class MergeAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.mergeBatch", { data: { params: { gridId: "CONTACT_LIST", entityTypeId: 3, entityIds: [ 100, 101 ] } } });
 */
class MergeBatchAction extends Main\Engine\Action
{
	final public function run(array $params): ?array
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

		$entityIDs = isset($params['entityIds']) && is_array($params['entityIds']) ? $params['entityIds'] : null;
		if ($entityTypeID === \CCrmOwnerType::Deal)
		{
			$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
			if (!$restriction->hasPermission())
			{
				$restrictedItemIds = $restriction->filterRestrictedItemIds(
					$entityTypeID,
					$entityIDs
				);
				if (!empty($restrictedItemIds))
				{
					Container::getInstance()->getLocalization()->loadMessages();
					$this->addError(new Main\Error(Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR')));
					return null;
				}
			}
		}

		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory)
		{
			$this->addError(Crm\Controller\ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$effectiveEntityIDs = $this->filterExistsItems($factory, $entityIDs);
		if (empty($effectiveEntityIDs))
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
		if($factory->getItem($rootEntityID) === null)
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

	private function filterExistsItems(Crm\Service\Factory $factory, array $ids): array
	{
		$ids = array_map(static fn (mixed $id) => (int)$id, $ids);
		if (empty($ids))
		{
			return [];
		}

		$items = $factory->getItems([
			'select' => ['ID'],
			'filter' => ['@ID' => $ids],
		]);

		$existsIds = array_map(static fn (Item $item) => $item->getId(), $items);
		$isExistsCallback = static fn (int $id) => in_array($id, $existsIds, true);

		return array_filter($ids, $isExistsCallback);
	}
}
