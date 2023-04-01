<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Crm\QuoteTable;
use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class QuoteContactTable extends DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_quote_contact';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('QUOTE_ID'))
				->configurePrimary(),
			(new IntegerField('CONTACT_ID'))
				->configurePrimary(),
			(new IntegerField('SORT'))
				->configureRequired()
				->configureDefaultValue(0),
			(new IntegerField('ROLE_ID'))
				->configureRequired()
				->configureDefaultValue(EntityBinding::ROLE_UNDEFINED),
			(new BooleanField('IS_PRIMARY'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N'),
			(new Reference('QUOTE', QuoteTable::class, Join::on('this.QUOTE_ID', 'ref.ID'))),
		];
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$quoteID = isset($data['QUOTE_ID']) ? (int)$data['QUOTE_ID'] : 0;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must contains QUOTE_ID field.', 'data');
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
			'b_crm_quote_contact',
			array('QUOTE_ID', 'CONTACT_ID'),
			array('QUOTE_ID' => $quoteID, 'CONTACT_ID' => $contactID, 'SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary),
			array('SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get quote IDs are bound to specified contact.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getContactQuotesIDs($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult =  Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT QUOTE_ID FROM b_crm_quote_contact WHERE CONTACT_ID = {$contactID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['QUOTE_ID'];
		}
		return $results;
	}
	/**
	 * Get contact IDs are bound to specified quote.
	 * @param int $quoteID Quote ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getQuoteContactIDs($quoteID)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT CONTACT_ID FROM b_crm_quote_contact WHERE QUOTE_ID = {$quoteID} ORDER BY SORT ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['CONTACT_ID'];
		}
		return $results;
	}
	/**
	 * Get quote's bindings.
	 * @param int $quoteID Quote ID.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getQuoteBindings($quoteID)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT CONTACT_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_quote_contact WHERE QUOTE_ID = {$quoteID} ORDER BY SORT"
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
	 * Get binding map for quote's collection.
	 * @param array $quoteIDs Array of Quote IDs.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBulkQuoteBindings(array $quoteIDs)
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($quoteIDs, false);
		if (empty($quoteIDs))
		{
			return [];
		}
		$bindingMap = array();
		foreach($quoteIDs as $quoteID)
		{
			$bindingMap[$quoteID] = array();
		}

		$dbResult = self::getList(
			array(
				'filter' => array('@QUOTE_ID' => $quoteIDs),
				'select' => array('QUOTE_ID', 'CONTACT_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'),
				'order' => array('QUOTE_ID' => 'ASC', 'SORT' => 'ASC')
			)
		);
		while($ary = $dbResult->fetch())
		{
			$bindingMap[$ary['QUOTE_ID']][] = array(
				'CONTACT_ID' => (int)$ary['CONTACT_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $bindingMap;
	}
	/**
	 *  Get quote's binding count.
	 * @param int $quoteID Quote ID.
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getQuoteBindingCount($quoteID)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COUNT(*) CNT FROM b_crm_quote_contact WHERE QUOTE_ID = {$quoteID}"
		);

		$ary = $dbResult->fetch();
		return is_array($ary) ? (int)$ary['CNT'] : 0;
	}
	/**
	 * Check if quote has contacts.
	 * @param int $quoteID Quote ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function hasContacts($quoteID)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		$result = self::getList(
			array(
				'select' => array('QUOTE_ID'),
				'filter' => array('=QUOTE_ID' => $quoteID),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}
	/**
	 * Bind quote to contacts are specified by ID.
	 * @param int $quoteID Quote ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 */
	public static function bindContactIDs($quoteID, array $contactIDs)
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
			self::bindContacts($quoteID, $bindings);
		}
	}
	/**
	 * Bind quote to contacts.
	 * @param int $quoteID Quote ID.
	 * @param array $bindings Array of contact bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function bindContacts($quoteID, array $bindings)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
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

			$contactID = isset($binding['CONTACT_ID']) ? (int)$binding['CONTACT_ID'] : 0;
			if($contactID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'QUOTE_ID' => $quoteID,
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
				"UPDATE b_crm_quote SET CONTACT_ID =
				(SELECT MIN(CONTACT_ID) FROM b_crm_quote_contact WHERE IS_PRIMARY = 'Y' AND QUOTE_ID = {$quoteID})
				WHERE ID = {$quoteID}"
			);
		}
	}
	/**
	 * Unbind specified quote from specified contacts.
	 * @param int $quoteID Quote ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContactIDs($quoteID, array $contactIDs)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
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
			"DELETE FROM b_crm_quote_contact WHERE QUOTE_ID = {$quoteID} AND CONTACT_ID IN({$values})"
		);

		$connection->queryExecute(
			/** @lang text*/
			"UPDATE b_crm_quote SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_quote_contact WHERE IS_PRIMARY = 'Y' AND QUOTE_ID = {$quoteID})
			WHERE ID = {$quoteID}"
		);
	}
	/**
	 * Unbind specified quote from specified contacts.
	 * @param int $quoteID Quote ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindContacts($quoteID, array $bindings)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		self::unbindContactIDs($quoteID, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $bindings));
	}
	/**
	 * Unbind specified quote from all contacts.
	 * @param int $quoteID Quote ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllContacts($quoteID)
	{
		$quoteID = (int)$quoteID;
		if($quoteID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'quoteID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_quote_contact WHERE QUOTE_ID = {$quoteID}"
		);
		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_crm_quote SET CONTACT_ID = NULL WHERE ID = {$quoteID}"
		);
	}
	/**
	 * Unbind specified contact from all quotes.
	 * @param int $contactID Contact ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function unbindAllQuotes($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_quote_contact WHERE CONTACT_ID = {$contactID}"
		);
		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_crm_quote SET CONTACT_ID =
			(SELECT MIN(CONTACT_ID) FROM b_crm_quote_contact t WHERE t.QUOTE_ID = b_crm_quote.ID)
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

		if($entityTypeID !== \CCrmOwnerType::Contact && $entityTypeID !== \CCrmOwnerType::Quote)
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
				return "INNER JOIN b_crm_quote_contact QC ON QC.CONTACT_ID IN({$slug}) AND QC.QUOTE_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Quote)
			{
				return "INNER JOIN b_crm_quote_contact QC ON QC.QUOTE_ID IN({$slug}) AND QC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		elseif($qty === 1)
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_quote_contact QC ON QC.CONTACT_ID = {$effectiveIDs[0]} AND QC.QUOTE_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Quote)
			{
				return "INNER JOIN b_crm_quote_contact QC ON QC.QUOTE_ID = {$effectiveIDs[0]} AND QC.CONTACT_ID = {$tableAlias}.ID";
			}
		}
		return "";
	}
	/**
	 * Unbind all quotes from seed contact and bind to target contact
	 * @param int $seedContactID Seed contact ID.
	 * @param int $targContactID Target contact ID.
	 * @throws Main\ArgumentException
	 */
	public static function rebindAllQuotes($seedContactID, $targContactID)
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
			"SELECT QUOTE_ID FROM b_crm_quote_contact WHERE CONTACT_ID = {$seedContactID}"
		);

		while($fields = $dbResult->fetch())
		{
			$quoteID = (int)$fields['QUOTE_ID'];
			$bindings = self::getQuoteBindings($quoteID);
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

			self::unbindContactIDs($quoteID, array($seedContactID));

			$isPrimary = isset($seedBinding['IS_PRIMARY']) && $seedBinding['IS_PRIMARY'] === 'Y';
			if(!is_array($targBinding))
			{
				$seedBinding['CONTACT_ID'] = $targContactID;
				self::bindContacts($quoteID, array($seedBinding));
			}
			elseif($isPrimary)
			{
				$targBinding['IS_PRIMARY'] = 'Y';
				self::bindContacts($quoteID, array($targBinding));
			}
		}
	}
}
