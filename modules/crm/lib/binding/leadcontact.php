<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Crm\LeadTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class LeadContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_LeadContact_Query query()
 * @method static EO_LeadContact_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_LeadContact_Result getById($id)
 * @method static EO_LeadContact_Result getList(array $parameters = [])
 * @method static EO_LeadContact_Entity getEntity()
 * @method static \Bitrix\Crm\Binding\EO_LeadContact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Binding\EO_LeadContact_Collection createCollection()
 * @method static \Bitrix\Crm\Binding\EO_LeadContact wakeUpObject($row)
 * @method static \Bitrix\Crm\Binding\EO_LeadContact_Collection wakeUpCollection($rows)
 */
class LeadContactTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_lead_contact';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'LEAD_ID' => array('primary' => true, 'data_type' => 'integer'),
			'CONTACT_ID' => array('primary' => true, 'data_type' => 'integer'),
			'SORT' => array('data_type' => 'integer', 'default_value' => 0),
			'ROLE_ID' => array('data_type' => 'integer', 'default_value' => 0),
			'IS_PRIMARY' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'N'),
			(new Reference('LEAD', LeadTable::class, Join::on('this.LEAD_ID', 'ref.ID'))),
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$leadID = isset($data['LEAD_ID']) ? (int)$data['LEAD_ID'] : 0;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must contains LEAD_ID field.', 'data');
		}

		$contactID = isset($data['CONTACT_ID']) ? (int)$data['CONTACT_ID'] : 0;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must contains CONTACT_ID field.', 'data');
		}

		$sort = isset($data['SORT']) ? (int)$data['SORT'] : 0;
		$roleID = isset($data['ROLE_ID']) ? (int)$data['ROLE_ID'] : 0;
		$primary = isset($data['IS_PRIMARY']) && mb_strtoupper($data['IS_PRIMARY']) === 'Y' ? 'Y' : 'N';

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_lead_contact',
			array('LEAD_ID', 'CONTACT_ID'),
			array('LEAD_ID' => $leadID, 'CONTACT_ID' => $contactID, 'SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary),
			array('SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get Lead IDs are bound to specified contact.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getContactLeadIDs($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult =  Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT LEAD_ID FROM b_crm_lead_contact WHERE CONTACT_ID = {$contactID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['LEAD_ID'];
		}
		return $results;
	}
	/**
	 * Get Contact IDs are bound to specified Lead.
	 * @param int $leadID Lead ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getLeadContactIDs($leadID)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		$dbResult = Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT CONTACT_ID FROM b_crm_lead_contact WHERE LEAD_ID = {$leadID} ORDER BY SORT ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['CONTACT_ID'];
		}
		return $results;
	}
	/**
	 * Get Lead's bindings.
	 * @param int $leadID Lead ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getLeadBindings($leadID)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		$dbResult = Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT CONTACT_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_lead_contact WHERE LEAD_ID = {$leadID} ORDER BY SORT"
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
	 * Get binding map for lead's collection.
	 * @param array $leadsIDs Array of Lead IDs.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBulkLeadBindings(array $leadsIDs)
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($leadsIDs, false);
		if (empty($leadsIDs))
		{
			return [];
		}
		$bindingMap = array();
		foreach($leadsIDs as $leadID)
		{
			$bindingMap[$leadID] = array();
		}

		$dbResult = self::getList(
			array(
				'filter' => array('@LEAD_ID' => $leadsIDs),
				'select' => array('LEAD_ID', 'CONTACT_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'),
				'order' => array('LEAD_ID' => 'ASC', 'SORT' => 'ASC')
			)
		);
		while($ary = $dbResult->fetch())
		{
			$bindingMap[$ary['LEAD_ID']][] = array(
				'CONTACT_ID' => (int)$ary['CONTACT_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $bindingMap;
	}
	/**
	 *  Get Lead's binding count.
	 * @param $leadID
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getLeadBindingCount($leadID)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		$dbResult = Main\Application::getConnection()->query(
		/** @lang text*/
			"SELECT COUNT(*) CNT FROM b_crm_lead_contact WHERE LEAD_ID = {$leadID}"
		);

		$ary = $dbResult->fetch();
		return is_array($ary) ? (int)$ary['CNT'] : 0;
	}
	/**
	 * Check if Lead has Contacts.
	 * @param int $leadID Lead ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function hasContacts($leadID)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		$result = self::getList(
			array(
				'select' => array('LEAD_ID'),
				'filter' => array('=LEAD_ID' => $leadID),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}
	/**
	 * Bind Lead to Contacts are specified by ID.
	 * @param int $leadID Lead ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 */
	public static function bindContactIDs($leadID, array $contactIDs)
	{
		$bindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, $contactIDs);
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
			self::bindContacts($leadID, $bindings);
		}
	}
	/**
	 * Bind Lead to Contacts.
	 * @param int $leadID Lead ID.
	 * @param array $bindings Array of contact bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bindContacts($leadID, array $bindings)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
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
			if(!is_array($binding))
			{
				continue;
			}

			$contactID = isset($binding['CONTACT_ID']) ? (int)$binding['CONTACT_ID'] : 0;
			if($contactID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'LEAD_ID' => $leadID,
					'CONTACT_ID' => $contactID,
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
				"UPDATE b_crm_lead SET CONTACT_ID =
				(SELECT MIN(CONTACT_ID) FROM b_crm_lead_contact WHERE IS_PRIMARY = 'Y' AND LEAD_ID = {$leadID})
				WHERE ID = {$leadID}"
			);
			Container::getInstance()->getLeadBroker()?->deleteCache($leadID);
		}
	}
	/**
	 * Unbind specified Lead from specified contacts.
	 * @param int $leadID Lead ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContactIDs($leadID, array $contactIDs)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
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
			"DELETE FROM b_crm_lead_contact WHERE LEAD_ID = {$leadID} AND CONTACT_ID IN({$values})"
		);

		$connection->queryExecute(
		/** @lang text*/
			"UPDATE b_crm_lead SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_lead_contact WHERE IS_PRIMARY = 'Y' AND LEAD_ID = {$leadID})
			WHERE ID = {$leadID}"
		);
		Container::getInstance()->getLeadBroker()?->deleteCache($leadID);
	}
	/**
	 * Unbind specified Lead from specified Contacts.
	 * @param int $leadID Lead ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContacts($leadID, array $bindings)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		self::unbindContactIDs($leadID, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $bindings));
	}
	/**
	 * Unbind specified Lead from all contacts.
	 * @param int $leadID Lead ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllContacts($leadID)
	{
		$leadID = (int)$leadID;
		if($leadID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'leadID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_lead_contact WHERE LEAD_ID = {$leadID}"
		);
		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_crm_lead SET CONTACT_ID = NULL WHERE ID = {$leadID}"
		);
		Container::getInstance()->getLeadBroker()?->deleteCache($leadID);
	}
	/**
	 * Unbind specified Contact from all Leads.
	 * @param int $contactID Contact ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllLeads($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_lead_contact WHERE CONTACT_ID = {$contactID}"
		);
		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_crm_lead SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_lead_contact t WHERE t.LEAD_ID = b_crm_lead.ID)
			WHERE CONTACT_ID = {$contactID}"
		);
		Container::getInstance()->getLeadBroker()?->resetAllCache();
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

		if($entityTypeID !== \CCrmOwnerType::Contact && $entityTypeID !== \CCrmOwnerType::Lead)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entityIDs = is_array($entityID) ? $entityID : array($entityID);
		$effectiveIDs = array();
		foreach($entityIDs as $ID)
		{
			$ID = (int)$ID;
			if($ID > 0)
			{
				$effectiveIDs[] = $ID;
			}
		}

		$qty = count($effectiveIDs);
		if($qty > 1)
		{
			$slug = implode(',', $effectiveIDs);
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_lead_contact DC ON DC.CONTACT_ID IN({$slug}) AND DC.LEAD_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Lead)
			{
				return "INNER JOIN b_crm_lead_contact DC ON DC.LEAD_ID IN({$slug}) AND DC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		elseif($qty === 1)
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_lead_contact DC ON DC.CONTACT_ID = {$effectiveIDs[0]} AND DC.LEAD_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Lead)
			{
				return "INNER JOIN b_crm_lead_contact DC ON DC.LEAD_ID = {$effectiveIDs[0]} AND DC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		return "";
	}
	/**
	 * Unbind all Leads from seed Contact and bind to target Contact
	 * @param int $seedContactID Seed contact ID.
	 * @param int $targContactID Target contact ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllLeads($seedContactID, $targContactID)
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
			"SELECT LEAD_ID FROM b_crm_lead_contact WHERE CONTACT_ID = {$seedContactID}"
		);

		while($fields = $dbResult->fetch())
		{
			$leadID = (int)$fields['LEAD_ID'];
			$bindings = self::getLeadBindings($leadID);
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

			self::unbindContactIDs($leadID, array($seedContactID));

			$isPrimary = isset($seedBinding['IS_PRIMARY']) && $seedBinding['IS_PRIMARY'] === 'Y';
			if(!is_array($targBinding))
			{
				$seedBinding['CONTACT_ID'] = $targContactID;
				self::bindContacts($leadID, array($seedBinding));
			}
			elseif($isPrimary)
			{
				$targBinding['IS_PRIMARY'] = 'Y';
				self::bindContacts($leadID, array($targBinding));
			}
		}
	}
}
