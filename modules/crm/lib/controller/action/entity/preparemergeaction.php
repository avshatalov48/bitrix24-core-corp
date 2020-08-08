<?php
namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.prepareMerge", { data: { params: { gridId: "DEAL_LIST", entityTypeId: 2, entityIds: [ 100, 101] } } });
 */
class PrepareMergeAction extends Main\Engine\Action
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

		$entityIDs = isset($params['entityIds']) && is_array($params['entityIds']) ? $params['entityIds'] : null;
		if(!is_array($entityIDs) || empty($entityIDs))
		{
			$this->addError(new Main\Error('The parameter entityIds is required.'));
			return null;
		}

		if(count($entityIDs) < 2)
		{
			$this->addError(new Main\Error('The parameter entityIds must contains at least two elements.'));
			return null;
		}

		if(!isset($_SESSION['CRM_ENTITY_MERGE_DATA']))
		{
			$_SESSION['CRM_ENTITY_MERGE_DATA'] = [];
		}

		sort($entityIDs, SORT_NUMERIC);
		$hash = md5(
			\CCrmOwnerType::ResolveName($entityTypeID).':'.mb_strtoupper($gridID).':'.implode(',', $entityIDs)
		);

		$_SESSION['CRM_ENTITY_MERGE_DATA'][$hash] = [
			'HASH' => $hash,
			'GRID_ID' => $gridID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_IDS' => $entityIDs
		];

		if(isset($_SESSION['CRM_ENTITY_MERGE_PROGRESS']))
		{
			unset($_SESSION['CRM_ENTITY_MERGE_PROGRESS'][$hash]);
		}

		return [ 'hash' => $hash ];
	}
}