<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Crm\DealTable;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class DealContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DealContact_Query query()
 * @method static EO_DealContact_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_DealContact_Result getById($id)
 * @method static EO_DealContact_Result getList(array $parameters = array())
 * @method static EO_DealContact_Entity getEntity()
 * @method static \Bitrix\Crm\Binding\EO_DealContact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Binding\EO_DealContact_Collection createCollection()
 * @method static \Bitrix\Crm\Binding\EO_DealContact wakeUpObject($row)
 * @method static \Bitrix\Crm\Binding\EO_DealContact_Collection wakeUpCollection($rows)
 */
class DealContactTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_deal_contact';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'DEAL_ID' => array('primary' => true, 'data_type' => 'integer'),
			'CONTACT_ID' => array('primary' => true, 'data_type' => 'integer'),
			'SORT' => array('data_type' => 'integer', 'default_value' => 0),
			'ROLE_ID' => array('data_type' => 'integer', 'default_value' => 0),
			'IS_PRIMARY' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'N'),
			(new Reference('DEAL', DealTable::class, Join::on('this.DEAL_ID', 'ref.ID'))),
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$dealID = isset($data['DEAL_ID']) ? (int)$data['DEAL_ID'] : 0;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must contains DEAL_ID field.', 'data');
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
			'b_crm_deal_contact',
			array('DEAL_ID', 'CONTACT_ID'),
			array('DEAL_ID' => $dealID, 'CONTACT_ID' => $contactID, 'SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary),
			array('SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get deal IDs are bound to specified contact.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getContactDealIDs($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult =  Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT DEAL_ID FROM b_crm_deal_contact WHERE CONTACT_ID = {$contactID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['DEAL_ID'];
		}
		return $results;
	}
	/**
	 * Get contact IDs are bound to specified deal.
	 * @param int $dealID Deal ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getDealContactIDs($dealID)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT CONTACT_ID FROM b_crm_deal_contact WHERE DEAL_ID = {$dealID} ORDER BY SORT ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['CONTACT_ID'];
		}
		return $results;
	}

	public static function getDealsContactIds(array $dealIds): array
	{
		$dealIds = array_map('intval', $dealIds);
		$dealIds = array_filter($dealIds, static function ($id) {
			return $id > 0;
		});

		$result = [];

		if(count($dealIds) <= 0)
		{
			return $result;
		}
		$collection = static::getList([
			'select' => ['CONTACT_ID'],
			'filter' => [
				'@DEAL_ID' => $dealIds,
			],
		])->fetchCollection();
		foreach ($collection as $item)
		{
			$result[$item->getDealId()][] = $item->getContactId();
		}

		return $result;
	}

	/**
	 * Get deal's bindings.
	 * @param int $dealID Deal ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getDealBindings($dealID)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT CONTACT_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_deal_contact WHERE DEAL_ID = {$dealID} ORDER BY SORT"
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
	 * Get binding map for deal's collection.
	 * @param array $dealIDs Array of Deal IDs.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBulkDealBindings(array $dealIDs)
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($dealIDs, false);
		if (empty($dealIDs))
		{
			return [];
		}

		$bindingMap = array();
		foreach($dealIDs as $dealID)
		{
			$bindingMap[$dealID] = array();
		}

		$dbResult = self::getList(
			array(
				'filter' => array('@DEAL_ID' => $dealIDs),
				'select' => array('DEAL_ID', 'CONTACT_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'),
				'order' => array('DEAL_ID' => 'ASC', 'SORT' => 'ASC')
			)
		);
		while($ary = $dbResult->fetch())
		{
			$bindingMap[$ary['DEAL_ID']][] = array(
				'CONTACT_ID' => (int)$ary['CONTACT_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $bindingMap;
	}
	/**
	 *  Get deal's binding count.
	 * @param $dealID
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getDealBindingCount($dealID)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COUNT(*) CNT FROM b_crm_deal_contact WHERE DEAL_ID = {$dealID}"
		);

		$ary = $dbResult->fetch();
		return is_array($ary) ? (int)$ary['CNT'] : 0;
	}
	/**
	 * Check if deal has contacts.
	 * @param int $dealID Deal ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function hasContacts($dealID)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$result = self::getList(
			array(
				'select' => array('DEAL_ID'),
				'filter' => array('=DEAL_ID' => $dealID),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}
	/**
	 * Bind deal to contacts are specified by ID.
	 * @param int $dealID Deal ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 */
	public static function bindContactIDs($dealID, array $contactIDs)
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
			self::bindContacts($dealID, $bindings);
		}
	}
	/**
	 * Bind deal to contacts.
	 * @param int $dealID Deal ID.
	 * @param array $bindings Array of contact bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bindContacts($dealID, array $bindings)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
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
					'DEAL_ID' => $dealID,
					'CONTACT_ID' => $contactID,
					'SORT' => isset($binding['SORT']) ? (int)$binding['SORT'] : (10 * ($i + 1)),
					'ROLE_ID' => isset($binding['ROLE_ID']) ? (int)$binding['ROLE_ID'] : EntityBinding::ROLE_UNDEFINED,
					'IS_PRIMARY' => isset($binding['IS_PRIMARY']) ? $binding['IS_PRIMARY'] : ''
				)
			);
			$processed++;
		}

		if($processed > 0)
		{
			Main\Application::getConnection()->queryExecute(
				/** @lang text*/
				"UPDATE b_crm_deal SET CONTACT_ID =
				(SELECT MIN(CONTACT_ID) FROM b_crm_deal_contact WHERE IS_PRIMARY = 'Y' AND DEAL_ID = {$dealID})
				WHERE ID = {$dealID}"
			);
		}
	}
	/**
	 * Unbind specified deal from specified contacts.
	 * @param int $dealID Deal ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContactIDs($dealID, array $contactIDs)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
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
			"DELETE FROM b_crm_deal_contact WHERE DEAL_ID = {$dealID} AND CONTACT_ID IN({$values})"
		);

		$connection->queryExecute(
			/** @lang text*/
			"UPDATE b_crm_deal SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_deal_contact WHERE IS_PRIMARY = 'Y' AND DEAL_ID = {$dealID})
			WHERE ID = {$dealID}"
		);
	}
	/**
	 * Unbind specified deal from specified contacts.
	 * @param int $dealID Deal ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContacts($dealID, array $bindings)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		self::unbindContactIDs($dealID, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $bindings));
	}
	/**
	 * Unbind specified deal from all contacts.
	 * @param int $dealID Deal ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllContacts($dealID)
	{
		$dealID = (int)$dealID;
		if($dealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'dealID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_deal_contact WHERE DEAL_ID = {$dealID}"
		);
		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_deal SET CONTACT_ID = NULL WHERE ID = {$dealID}"
		);
	}
	/**
	 * Unbind specified contact from all deals.
	 * @param int $contactID Contact ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllDeals($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_deal_contact WHERE CONTACT_ID = {$contactID}"
		);
		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_crm_deal SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_deal_contact t WHERE t.DEAL_ID = b_crm_deal.ID)
			WHERE CONTACT_ID = {$contactID}"
		);
	}
	/**
	 * Prepage SQL join filter condition for specified entity.
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

		if($entityTypeID !== \CCrmOwnerType::Contact && $entityTypeID !== \CCrmOwnerType::Deal)
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
				return "INNER JOIN b_crm_deal_contact DC ON DC.CONTACT_ID IN({$slug}) AND DC.DEAL_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Deal)
			{
				return "INNER JOIN b_crm_deal_contact DC ON DC.DEAL_ID IN({$slug}) AND DC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		elseif($qty === 1)
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_deal_contact DC ON DC.CONTACT_ID = {$effectiveIDs[0]} AND DC.DEAL_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Deal)
			{
				return "INNER JOIN b_crm_deal_contact DC ON DC.DEAL_ID = {$effectiveIDs[0]} AND DC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		return "";
	}
	/**
	 * Unbind all contacts from seed deal and bind to target deal
	 * @param int $seedDealID Seed deal ID.
	 * @param int $targDealID Target deal ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllContacts($seedDealID, $targDealID)
	{
		$seedDealID = (int)$seedDealID;
		if($seedDealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'seedDealID');
		}

		$targDealID = (int)$targDealID;
		if($targDealID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'targDealID');
		}

		//Combine contacts from seed and target and bind to target.
		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
		/** @lang text */
			"SELECT CONTACT_ID FROM b_crm_deal_contact
				WHERE DEAL_ID IN ({$seedDealID}, {$targDealID}) GROUP BY CONTACT_ID"
		);

		$contactIDs = array();
		while($fields = $dbResult->fetch())
		{
			$contactIDs[] = (int)$fields['CONTACT_ID'];
		}

		if(!empty($contactIDs))
		{
			self::bindContactIDs($targDealID, $contactIDs);
		}

		//Clear seed bindings
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_deal_contact WHERE DEAL_ID = {$seedDealID}"
		);
	}
	/**
	 * Unbind all deals from seed contact and bind to target contact
	 * @param int $seedContactID Seed contact ID.
	 * @param int $targContactID Target contact ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllDeals($seedContactID, $targContactID)
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
			"SELECT DEAL_ID FROM b_crm_deal_contact WHERE CONTACT_ID = {$seedContactID}"
		);

		while($fields = $dbResult->fetch())
		{
			$dealID = (int)$fields['DEAL_ID'];
			$bindings = self::getDealBindings($dealID);
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

			self::unbindContactIDs($dealID, array($seedContactID));

			$isPrimary = isset($seedBinding['IS_PRIMARY']) && $seedBinding['IS_PRIMARY'] === 'Y';
			if(!is_array($targBinding))
			{
				$seedBinding['CONTACT_ID'] = $targContactID;
				self::bindContacts($dealID, array($seedBinding));
			}
			elseif($isPrimary)
			{
				$targBinding['IS_PRIMARY'] = 'Y';
				self::bindContacts($dealID, array($targBinding));
			}
		}
	}
}
