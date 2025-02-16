<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ContactCompanyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ContactCompany_Query query()
 * @method static EO_ContactCompany_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ContactCompany_Result getById($id)
 * @method static EO_ContactCompany_Result getList(array $parameters = [])
 * @method static EO_ContactCompany_Entity getEntity()
 * @method static \Bitrix\Crm\Binding\EO_ContactCompany createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Binding\EO_ContactCompany_Collection createCollection()
 * @method static \Bitrix\Crm\Binding\EO_ContactCompany wakeUpObject($row)
 * @method static \Bitrix\Crm\Binding\EO_ContactCompany_Collection wakeUpCollection($rows)
 */
class ContactCompanyTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_contact_company';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'CONTACT_ID' => array('primary' => true, 'data_type' => 'integer'),
			'COMPANY_ID' => array('primary' => true, 'data_type' => 'integer'),
			'SORT' => array('data_type' => 'integer', 'default_value' => 0),
			'ROLE_ID' => array('data_type' => 'integer', 'default_value' => 0),
			'IS_PRIMARY' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'N'),
			(new Reference('CONTACT', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),
			(new Reference('COMPANY', CompanyTable::class, Join::on('this.COMPANY_ID', 'ref.ID'))),
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$contactID = isset($data['CONTACT_ID']) ? (int)$data['CONTACT_ID'] : 0;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must contains CONTACT_ID field.', 'data');
		}

		$companyID = isset($data['COMPANY_ID']) ? (int)$data['COMPANY_ID'] : 0;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must contains COMPANY_ID field.', 'data');
		}

		$sort = isset($data['SORT']) ? (int)$data['SORT'] : 0;
		$roleID = isset($data['ROLE_ID']) ? (int)$data['ROLE_ID'] : 0;
		$primary = isset($data['IS_PRIMARY']) && mb_strtoupper($data['IS_PRIMARY']) === 'Y' ? 'Y' : 'N';

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_contact_company',
			array('CONTACT_ID', 'COMPANY_ID'),
			array('CONTACT_ID' => $contactID, 'COMPANY_ID' => $companyID, 'SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary),
			array('SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get company IDs are bound to specified contact.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getContactCompanyIDs($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult =  Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COMPANY_ID FROM b_crm_contact_company WHERE CONTACT_ID = {$contactID} ORDER BY SORT ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['COMPANY_ID'];
		}
		return $results;
	}
	/**
	 * Get contact IDs are bound to specified company.
	 * @param int $companyID Company ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getCompanyContactIDs($companyID)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'companyID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT CONTACT_ID FROM b_crm_contact_company WHERE COMPANY_ID = {$companyID} ORDER BY CONTACT_ID ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['CONTACT_ID'];
		}
		return $results;
	}
	/**
	 * Get contacts's bindings.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getContactBindings($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COMPANY_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_contact_company WHERE CONTACT_ID = {$contactID} ORDER BY SORT"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = array(
				'COMPANY_ID' => (int)$ary['COMPANY_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $results;
	}
	/**
	 * Get binding map for contact's collection.
	 * @param array $contactIDs Array of Contact IDs.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBulkContactBindings(array $contactIDs)
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($contactIDs, false);
		if (empty($contactIDs))
		{
			return [];
		}

		$bindingMap = array();
		foreach($contactIDs as $contactID)
		{
			$bindingMap[$contactID] = array();
		}

		$dbResult = self::getList(
			array(
				'filter' => array('@CONTACT_ID' => $contactIDs),
				'select' => array('COMPANY_ID', 'CONTACT_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'),
				'order' => array('CONTACT_ID' => 'ASC', 'SORT' => 'ASC')
			)
		);
		while($ary = $dbResult->fetch())
		{
			$bindingMap[$ary['CONTACT_ID']][] = array(
				'COMPANY_ID' => (int)$ary['COMPANY_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $bindingMap;
	}
	/**
	 *  Get contact's binding count.
	 * @param int $contactID Contact ID.
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getContactBindingCount($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COUNT(*) CNT FROM b_crm_contact_company WHERE CONTACT_ID = {$contactID}"
		);

		$ary = $dbResult->fetch();
		return is_array($ary) ? (int)$ary['CNT'] : 0;
	}
	/**
	 * Get company's bindings.
	 * @param int $companyID Company ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getCompanyBindings($companyID)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'companyID');
		}

		$dbResult = Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT CONTACT_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_contact_company WHERE COMPANY_ID = {$companyID} ORDER BY SORT"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = array(
				'CONTACT_ID' => (int)$ary['CONTACT_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $results;
	}
	/**
	 * Get binding map for company's collection.
	 *
	 * @param array $companyIDs Array of Company IDs.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBulkCompanyBindings(array $companyIDs): array
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($companyIDs, false);
		if (empty($companyIDs))
		{
			return [];
		}

		$bindingMap = [];
		foreach ($companyIDs as $companyID)
		{
			$bindingMap[$companyID] = [];
		}

		$dbResult = self::getList([
			'filter' => ['@COMPANY_ID' => $companyIDs],
			'select' => ['COMPANY_ID', 'CONTACT_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'],
			'order' => ['COMPANY_ID' => 'ASC', 'SORT' => 'ASC']
		]);
		while($ary = $dbResult->fetch())
		{
			$bindingMap[$ary['COMPANY_ID']][] = [
				'CONTACT_ID' => (int)$ary['CONTACT_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			];
		}
		return $bindingMap;
	}
	/**
	 * Check if contact has companies.
	 * @param int $contactID Contact ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function hasCompanies($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$result = self::getList(
			array(
				'select' => array('CONTACT_ID'),
				'filter' => array('=CONTACT_ID' => $contactID),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}
	/**
	 * Bind contact to companies are specified by ID.
	 * @param int $contactID Contact ID.
	 * @param array $companyIDs Array of company IDs.
	 * @return void
	 */
	public static function bindCompanyIDs($contactID, array $companyIDs)
	{
		$bindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Company, $companyIDs);
		$qty = count($bindings);
		if($qty > 0)
		{
			for($i = 0; $i < $qty; $i++)
			{
				if($i === 0)
				{
					$bindings[$i]['IS_PRIMARY'] = 'Y';
				}
				$bindings[$i]['SORT'] = 10 * ($i + 1);
			}
			self::bindCompanies($contactID, $bindings);
		}
	}
	/**
	 * Bind company to contacts specified by ID.
	 * @param int $companyID Company ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 */
	public static function bindContactIDs($companyID, array $contactIDs)
	{
		$bindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, $contactIDs);
		$qty = count($bindings);
		if($qty > 0)
		{
			for($i = 0; $i < $qty; $i++)
			{
				$bindings[$i]['IS_PRIMARY'] = 'Y';
				$bindings[$i]['SORT'] = 10;
			}
			self::bindContacts($companyID, $bindings);
		}
	}
	/**
	 * Bind contact to companies.
	 * @param int $contactID Contact ID.
	 * @param array $bindings Array of company bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bindCompanies($contactID, array $bindings)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		$processed = 0;
		for($i = 0; $i < $qty; $i++)
		{
			$binding = $bindings[$i];

			$companyID = isset($binding['COMPANY_ID']) ? (int)$binding['COMPANY_ID'] : 0;
			if($companyID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'CONTACT_ID' => $contactID,
					'COMPANY_ID' => $companyID,
					'SORT' => isset($binding['SORT']) ? (int)$binding['SORT'] : (10 * ($i + 1)),
					'ROLE_ID' => isset($binding['ROLE_ID']) ? (int)$binding['ROLE_ID'] : EntityBinding::ROLE_UNDEFINED,
					'IS_PRIMARY' => isset($binding['IS_PRIMARY']) ? $binding['IS_PRIMARY'] : ''
				)
			);
			$processed++;
		}

		if ($processed > 0)
		{
			Main\Application::getConnection()->queryExecute(
				/** @lang text*/
				"UPDATE b_crm_contact SET COMPANY_ID =
				(SELECT MIN(COMPANY_ID) FROM b_crm_contact_company WHERE IS_PRIMARY = 'Y' AND CONTACT_ID = {$contactID})
				WHERE ID = {$contactID}"
			);
			Container::getInstance()->getContactBroker()->deleteCache($contactID);
		}
	}
	/**
	 * Bind company to contacts.
	 * @param int $companyID Company ID.
	 * @param array $bindings Array of company bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bindContacts($companyID, array $bindings)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'companyID');
		}

		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		$affectedIDs = array();
		for($i = 0; $i < $qty; $i++)
		{
			$binding = $bindings[$i];

			$contactID = isset($binding['CONTACT_ID']) ? (int)$binding['CONTACT_ID'] : 0;
			if($contactID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'CONTACT_ID' => $contactID,
					'COMPANY_ID' => $companyID,
					'SORT' => isset($binding['SORT']) ? (int)$binding['SORT'] : (10 * ($i + 1)),
					'ROLE_ID' => isset($binding['ROLE_ID']) ? (int)$binding['ROLE_ID'] : EntityBinding::ROLE_UNDEFINED,
					'IS_PRIMARY' => isset($binding['IS_PRIMARY']) ? $binding['IS_PRIMARY'] : ''
				)
			);
			$affectedIDs[] = $contactID;
		}

		if(!empty($affectedIDs))
		{
			$values = implode(',', $affectedIDs);

			Main\Application::getConnection()->queryExecute(
			/** @lang text*/
				"UPDATE b_crm_contact_company SET IS_PRIMARY =
				(CASE WHEN COMPANY_ID = {$companyID} THEN 'Y' ELSE 'N' END)
				WHERE CONTACT_ID IN({$values})"
			);

			Main\Application::getConnection()->queryExecute(
				/** @lang text*/
				"UPDATE b_crm_contact SET COMPANY_ID =
				(SELECT MIN(COMPANY_ID) FROM b_crm_contact_company t WHERE t.IS_PRIMARY = 'Y' AND t.CONTACT_ID = b_crm_contact.ID)
				WHERE ID IN({$values})"
			);
			Container::getInstance()->getContactBroker()->resetAllCache();
		}
	}
	/**
	 * Unbind specified contact from specified companies.
	 * @param int $contactID Contact ID.
	 * @param array $companyIDs Array of company IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindCompanyIDs($contactID, array $companyIDs)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$companyIDs = array_filter(array_map('intval', $companyIDs));
		if(empty($companyIDs))
		{
			return;
		}

		$connection = Main\Application::getConnection();

		$values = implode(',', $companyIDs);
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_contact_company WHERE CONTACT_ID = {$contactID} AND COMPANY_ID IN({$values})"
		);

		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_contact SET COMPANY_ID =
			(SELECT MIN(COMPANY_ID) FROM b_crm_contact_company t WHERE t.IS_PRIMARY = 'Y' AND t.CONTACT_ID = b_crm_contact.ID)
			WHERE ID = {$contactID}"
		);
		Container::getInstance()->getContactBroker()->deleteCache($contactID);
	}
	/**
	 * Unbind specified contact from specified companies.
	 * @param int $contactID Contact ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindCompanies($contactID, array $bindings)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		self::unbindCompanyIDs($contactID, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Company, $bindings));
	}
	/**
	 * Unbind specified contact from all companies.
	 * @param int $contactID Contact ID.
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function unbindAllCompanies($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'ID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_contact_company WHERE CONTACT_ID = {$contactID}"
		);
		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_contact SET COMPANY_ID = NULL WHERE ID = {$contactID}"
		);
		Container::getInstance()->getContactBroker()->deleteCache($contactID);
	}
	/**
	 * Unbind specified company from specified contacts.
	 * @param int $companyID Company ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContactIDs($companyID, array $contactIDs)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'companyID');
		}

		$contactIDs = array_filter(array_map('intval', $contactIDs));
		if(empty($contactIDs))
		{
			return;
		}

		$connection = Main\Application::getConnection();

		$values = implode(',', $contactIDs);
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_contact_company WHERE COMPANY_ID = {$companyID} AND CONTACT_ID IN({$values})"
		);

		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_contact SET COMPANY_ID =
			(SELECT MIN(COMPANY_ID) FROM b_crm_contact_company t WHERE t.CONTACT_ID = b_crm_contact.ID)
			WHERE COMPANY_ID = {$companyID} AND ID IN({$values})"
		);
		Container::getInstance()->getContactBroker()->deleteCache($contactID);
	}
	/**
	 * Unbind specified company from specified contacts.
	 * @param int $companyID Company ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContacts($companyID, array $bindings)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		self::unbindContactIDs($companyID, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $bindings));
	}
	/**
	 * Unbind specified company from all contacts.
	 * @param int $companyID Company ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllContacts($companyID)
	{
		$companyID = (int)$companyID;
		if($companyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'companyID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_contact_company WHERE COMPANY_ID = {$companyID}"
		);

		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_contact SET COMPANY_ID =
			(SELECT MIN(COMPANY_ID) FROM b_crm_contact_company t WHERE t.CONTACT_ID = b_crm_contact.ID)
			WHERE COMPANY_ID = {$companyID}"
		);
		Container::getInstance()->getContactBroker()->deleteCache($contactID);
	}
	/**
	 * Prepare SQL join filter condition for specified entity.
	 * @param int $entityTypeID Entity type ID for filter.
	 * @param int $entityID Entity ID for filter.
	 * @param string $tableAlias Alias of primary table.
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareFilterJoinSql($entityTypeID, $entityID, $tableAlias)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			return "INNER JOIN b_crm_contact_company CC ON CC.COMPANY_ID = {$entityID} AND CC.CONTACT_ID = {$tableAlias}.ID";
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return "INNER JOIN b_crm_contact_company CC ON CC.CONTACT_ID = {$entityID} AND CC.COMPANY_ID = {$tableAlias}.ID";
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
	}

	/**
	 * Prepare SQL join filter condition for specified entity by entity title.
	 * @param int $entityTypeID Entity type ID for filter.
	 * @param string $entityTitle Entity Title for filter.
	 * @param string $tableAlias Alias of primary table.
	 * @return string
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareFilterJoinSqlByTitle($entityTypeID, $entityTitle, $tableAlias)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_string($entityTitle))
		{
			$entityTitle = (string)$entityTitle;
		}

		if($entityTitle === '')
		{
			return '';
		}

		$where = new \CSQLWhere();

		$entityTitle = $where->ForLIKE($entityTitle);

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			return "INNER JOIN (
				SELECT CC.CONTACT_ID FROM b_crm_contact_company CC INNER JOIN b_crm_company C 
					ON C.ID = CC.COMPANY_ID AND C.TITLE LIKE '{$entityTitle}%' ESCAPE '!' GROUP BY CC.CONTACT_ID
			) CC ON {$tableAlias}.ID = CC.CONTACT_ID";
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
	}
	/**
	 * Unbind all contacts from seed company and bind to target company
	 * @param int $seedCompanyID Seed company ID.
	 * @param int $targCompanyID Target company ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllContacts($seedCompanyID, $targCompanyID)
	{
		$seedCompanyID = (int)$seedCompanyID;
		if($seedCompanyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'seedCompanyID');
		}

		$targCompanyID = (int)$targCompanyID;
		if($targCompanyID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'targCompanyID');
		}

		//Combine contacts from seed and target and bind to target.
		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
		/** @lang text */
			"SELECT CONTACT_ID FROM b_crm_contact_company
				WHERE COMPANY_ID IN ({$seedCompanyID}, {$targCompanyID}) GROUP BY CONTACT_ID"
		);

		$contactIDs = array();
		while($fields = $dbResult->fetch())
		{
			$contactIDs[] = (int)$fields['CONTACT_ID'];
		}

		if(!empty($contactIDs))
		{
			self::bindContactIDs($targCompanyID, $contactIDs);
		}

		//Clear seed bindings
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_contact_company WHERE COMPANY_ID = {$seedCompanyID}"
		);

		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_crm_contact SET COMPANY_ID = {$targCompanyID} WHERE COMPANY_ID = {$seedCompanyID}"
		);
		Container::getInstance()->getContactBroker()->resetAllCache();
	}
	/**
	 * Unbind all companies from seed contact and bind to target contact
	 * @param int $seedContactID Seed contact ID.
	 * @param int $targContactID Target contact ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllCompanies($seedContactID, $targContactID)
	{
		$seedContactID = (int)$seedContactID;
		if($seedContactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'seedContactID');
		}

		$targContactID = (int)$targContactID;
		if($targContactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'targContactID');
		}

		if($seedContactID === $targContactID)
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
		/** @lang text */
			"SELECT COMPANY_ID FROM b_crm_contact_company WHERE CONTACT_ID = {$seedContactID}"
		);

		while($fields = $dbResult->fetch())
		{
			$companyID = (int)$fields['COMPANY_ID'];
			$bindings = self::getCompanyBindings($companyID);
			$seedIndex = $targIndex = -1;
			for($i = 0, $l = count($bindings); $i < $l; $i++)
			{
				$binding = $bindings[$i];
				$contactID = (int)$binding['CONTACT_ID'];
				if($contactID === $seedContactID)
				{
					$seedIndex = $i;
				}
				elseif($contactID === $targContactID)
				{
					$targIndex = $i;
				}

				if($seedIndex >= 0 && $targIndex >= 0)
				{
					break;
				}
			}

			$seedBinding = $seedIndex >= 0 ? $bindings[$seedIndex] : null;
			$targBinding = $targIndex >= 0 ? $bindings[$targIndex] : null;

			if(!is_array($seedBinding))
			{
				continue;
			}

			self::unbindContactIDs($companyID, array($seedContactID));

			$isPrimary = isset($seedBinding['IS_PRIMARY']) && $seedBinding['IS_PRIMARY'] === 'Y';
			if(!is_array($targBinding))
			{
				$seedBinding['CONTACT_ID'] = $targContactID;
				self::bindContacts($companyID, array($seedBinding));
			}
			elseif($isPrimary)
			{
				$targBinding['IS_PRIMARY'] = 'Y';
				self::bindContacts($companyID, array($targBinding));
			}
		}
	}
}
