<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\LeadTable;

class Lead extends EntityBase
{
	/** @var Lead|null  */
	protected static $instance = null;

	/**
	 * @return Lead
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Lead();
		}
		return self::$instance;
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	//region Db
	protected function getDbEntity()
	{
		return Crm\LeadTable::getEntity();
	}
	public function getDbTableAlias()
	{
		return \CCrmLead::TABLE_ALIAS;
	}
	//endregion
	//region Permissions
	protected function buildPermissionSql(array $params)
	{
		return \CCrmLead::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}
	public function checkReadPermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmLead::CheckReadPermission($entityID, $userPermissions);
	}
	public function checkDeletePermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmLead::CheckDeletePermission($entityID, $userPermissions);
	}
	//endregion

	protected function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array
	{
		$dbResult = \CCrmLead::GetListEx(
			$order,
			$filter,
			false,
			$limit > 0 ? ['nTopCount' => $limit] : false,
			['ID']
		);

		$results = [];
		while ($fields = $dbResult->Fetch())
		{
			$results[] = (int)$fields['ID'];
		}

		return $results;
	}

	public function getCount(array $params)
	{
		$filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : array();
		$enablePermissionCheck = isset($params['enablePermissionCheck']) ? (bool)$params['enablePermissionCheck'] : true;
		if(!$enablePermissionCheck)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}
		return \CCrmLead::GetListEx(array(), $filter, array());
	}

	public function delete($entityID, array $options = array())
	{
		if(!isset($options['CHECK_DEPENDENCIES']))
		{
			$options['CHECK_DEPENDENCIES'] = true;
		}
		$entity = new \CCrmLead(false);
		if(!$entity->Delete($entityID, $options))
		{
			return array('MESSAGE' => $entity->LAST_ERROR);
		}
		return null;
	}

	/**
	 * Perform deferred cleaning of the related entities.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public function cleanup($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$eventEntity = new \CCrmEvent();
		$eventEntity->DeleteByElement(\CCrmOwnerType::LeadName, $entityID);
	}

	public static function getResponsibleID($entityID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($fields) && isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}

	public static function getSubsidiaryEntities($ID)
	{
		$dbResult = Crm\LeadTable::getList(
			array(
				'filter' => array('=ID' => $ID),
				'select' => array('ID', 'COMPANY_ID', 'CONTACT_ID', 'STATUS_SEMANTIC_ID')
			)
		);

		$fields = $dbResult->fetch();
		if(!(is_array($fields) && $fields['STATUS_SEMANTIC_ID'] === Crm\PhaseSemantics::SUCCESS))
		{
			return array();
		}

		$results = array();
		if(isset($fields['COMPANY_ID']) && $fields['COMPANY_ID'] > 0)
		{
			$results[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => (int)$fields['COMPANY_ID']);
		}

		if(isset($fields['CONTACT_ID']) && $fields['CONTACT_ID'] > 0)
		{
			$results[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => (int)$fields['CONTACT_ID']);
		}

		$dbResult = Crm\DealTable::getList(
			array(
				'filter' => array('=LEAD_ID' => $ID),
				'select' => array('ID')
			)
		);
		while($fields = $dbResult->fetch())
		{
			$results[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => (int)$fields['ID']);
		}

		return $results;
	}

	public static function getChildEntityIDs($ID, $childEntityTypeID)
	{
		if($childEntityTypeID === \CCrmOwnerType::Contact)
		{
			$dbResult = Crm\ContactTable::getList(
				array(
					'filter' => array('=LEAD_ID' => $ID),
					'select' => array('ID')
				)
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Company)
		{
			$dbResult = Crm\CompanyTable::getList(
				array(
					'filter' => array('=LEAD_ID' => $ID),
					'select' => array('ID')
				)
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Deal)
		{
			$dbResult = Crm\DealTable::getList(
				array(
					'filter' => array('=LEAD_ID' => $ID),
					'select' => array('ID')
				)
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Quote)
		{
			$dbResult = Crm\QuoteTable::getList(
				array(
					'filter' => array('=LEAD_ID' => $ID),
					'select' => array('ID')
				)
			);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($childEntityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = (int)$fields['ID'];
		}
		return $results;
	}

	public static function setChildEntityIDs($ID, $childEntityTypeID, array $childEntityIDs)
	{
		if($ID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'ID');
		}

		if($childEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'childEntityTypeID');
		}

		$childEntityIDs = array_filter(array_map('intval', $childEntityIDs));
		if(empty($childEntityIDs))
		{
			return;
		}

		$slug = implode(', ', $childEntityIDs);

		if($childEntityTypeID === \CCrmOwnerType::Contact)
		{
			Main\Application::getConnection()->queryExecute(
			/** @lang text*/
				"UPDATE b_crm_contact SET LEAD_ID = {$ID} WHERE ID IN({$slug})"
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Company)
		{
			Main\Application::getConnection()->queryExecute(
			/** @lang text*/
				"UPDATE b_crm_company SET LEAD_ID = {$ID} WHERE ID IN({$slug})"
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Deal)
		{
			Main\Application::getConnection()->queryExecute(
			/** @lang text*/
				"UPDATE b_crm_deal SET LEAD_ID = {$ID} WHERE ID IN({$slug})"
			);
		}
		elseif($childEntityTypeID === \CCrmOwnerType::Quote)
		{
			Main\Application::getConnection()->queryExecute(
			/** @lang text*/
				"UPDATE b_crm_quote SET LEAD_ID = {$ID} WHERE ID IN({$slug})"
			);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($childEntityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		Crm\Statistics\LeadConversionStatisticsEntry::register($ID);
	}

	/**
	 * Check if Entity exists.
	 * @param int $entityID Entity ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isExists($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$dbResult = LeadTable::getList(
			array(
				'select' => array('ID'),
				'filter' => array('=ID' => $entityID)
			)
		);

		return is_array($dbResult->fetch());
	}

	/**
	 * Select only existed entity IDs.
	 * @param array $entityIDs Deal IDs to check.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function selectExisted(array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return array();
		}

		$dbResult = LeadTable::getList(
			array(
				'filter' => array('@ID' => $entityIDs),
				'select' => array('ID')
			)
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['ID'];
		}
		return $results;
	}
}