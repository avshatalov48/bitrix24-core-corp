<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.prepareDeletion", { data: { params: { gridId: "DEAL_LIST", entityTypeId: 2, entityIds: [ 100, 101, 102 ] } } });
 */
class PrepareDeletionAction extends Main\Engine\Action
{
	final public function run(array $params)
	{
		if(!Crm\Security\EntityAuthorization::isAuthorized())
		{
			$this->addError(new Main\Error('Access denied.'));
			return null;
		}

		$gridID = isset($params['gridId']) ? $params['gridId'] : '';
		if($gridID === '')
		{
			$this->addError(new Main\Error('The parameter gridId is required.'));
			return null;
		}

		$entityTypeID = isset($params['entityTypeId']) ? (int)$params['entityTypeId'] : \CCrmOwnerType::Undefined;
		if($entityTypeID === \CCrmOwnerType::Undefined)
		{
			$this->addError(new Main\Error('The parameter entityTypeId is required.'));
			return null;
		}

		if(!isset($_SESSION['CRM_ENTITY_DELETION_DATA']))
		{
			$_SESSION['CRM_ENTITY_DELETION_DATA'] = [];
		}

		$entityIDs = isset($params['entityIds']) && is_array($params['entityIds']) ? $params['entityIds'] : null;
		if(is_array($entityIDs))
		{
			sort($entityIDs, SORT_NUMERIC);
			$hash = md5(
				\CCrmOwnerType::ResolveName($entityTypeID).':'.strtoupper($gridID).':'.implode(',', $entityIDs)
			);

			$_SESSION['CRM_ENTITY_DELETION_DATA'][$hash] = [
				'HASH' => $hash,
				'GRID_ID' => $gridID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_IDS' => $entityIDs
			];
		}
		else
		{
			$filterFields = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : array();
			if(empty($filterFields))
			{
				$filterOptions = new Main\UI\Filter\Options($gridID);
				$filterFields = $filterOptions->getFilter();
				$entityFilter = Crm\Filter\Factory::createEntityFilter(
					Crm\Filter\Factory::createEntitySettings($entityTypeID, $gridID)
				);

				$entityFilter->prepareListFilterParams($filterFields);
				Crm\Search\SearchEnvironment::convertEntityFilterValues($entityTypeID, $filterFields);

				\CCrmEntityHelper::PrepareMultiFieldFilter($filterFields, array(), '=%', false);
			}
			$extras = isset($params['extras']) && is_array($params['extras']) ? $params['extras'] : array();
			if($entityTypeID === \CCrmOwnerType::Deal)
			{
				if(isset($extras['CATEGORY_ID']) && $extras['CATEGORY_ID'] >= 0)
				{
					$filterFields['CATEGORY_ID'] = (int)$extras['CATEGORY_ID'];
				}

				$filterFields['IS_RECURRING'] = isset($extras['IS_RECURRING']) && $extras['IS_RECURRING'] === 'Y' ? 'Y' : 'N';
			}

			ksort($filterFields, SORT_STRING);
			$hash = md5(
				\CCrmOwnerType::ResolveName($entityTypeID)
				.':'.strtoupper($gridID)
				.':'.implode(',', array_map(function($k, $v){ return "{$k}:{$v}"; }, array_keys($filterFields), $filterFields))
			);

			$_SESSION['CRM_ENTITY_DELETION_DATA'][$hash] = [
				'HASH' => $hash,
				'GRID_ID' => $gridID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'FILTER' => $filterFields
			];
		}

		if(isset($_SESSION['CRM_ENTITY_DELETION_PROGRESS']))
		{
			unset($_SESSION['CRM_ENTITY_DELETION_PROGRESS'][$hash]);
		}

		return [ 'hash' => $hash ];
	}
}