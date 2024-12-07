<?php
namespace Bitrix\Crm\Observer\Entity;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ObserverTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Observer_Query query()
 * @method static EO_Observer_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Observer_Result getById($id)
 * @method static EO_Observer_Result getList(array $parameters = [])
 * @method static EO_Observer_Entity getEntity()
 * @method static \Bitrix\Crm\Observer\Entity\EO_Observer createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Observer\Entity\EO_Observer_Collection createCollection()
 * @method static \Bitrix\Crm\Observer\Entity\EO_Observer wakeUpObject($row)
 * @method static \Bitrix\Crm\Observer\Entity\EO_Observer_Collection wakeUpCollection($rows)
 */
class ObserverTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_observer';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ENTITY_TYPE_ID'))
				->configurePrimary(),
			(new IntegerField('ENTITY_ID'))
				->configurePrimary(),
			(new IntegerField('USER_ID'))
				->configurePrimary(),
			(new IntegerField('SORT'))
				->configureRequired()
				->configureDefaultValue(0),
			(new DatetimeField('CREATED_TIME'))
				->configureRequired(),
			(new DatetimeField('LAST_UPDATED_TIME'))
				->configureRequired(),
			(new Main\ORM\Fields\Relations\Reference(
				'DEAL',
				DealTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', \CCrmOwnerType::Deal)
			)),
			(new Main\ORM\Fields\Relations\Reference(
				'LEAD',
				LeadTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', \CCrmOwnerType::Lead)
			)),
			(new Main\ORM\Fields\Relations\Reference(
				'CONTACT',
				ContactTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', \CCrmOwnerType::Contact)
			)),
			(new Main\ORM\Fields\Relations\Reference(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', \CCrmOwnerType::Company)
			)),
		];
	}

	public static function cleanCache(): void
	{
		parent::cleanCache();

		\Bitrix\Crm\Observer\ObserverManager::resetBulkObserverIDsCache();
	}

	public static function upsert(array $data)
	{
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$userID = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		$sort = isset($data['SORT']) ? (int)$data['SORT'] : 0;

		$now = new Main\Type\DateTime();

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_observer',
			array('ENTITY_TYPE_ID', 'ENTITY_ID', 'USER_ID'),
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $userID,
				'SORT' => $sort,
				'CREATED_TIME' => $now,
				'LAST_UPDATED_TIME' => $now
			),
			array('SORT' => $sort, 'LAST_UPDATED_TIME' => $now)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}

		static::cleanCache();
	}

	public static function deleteByEntityTypeId(int $entityTypeId): void
	{
		static::deleteByFilter([
			'ENTITY_TYPE_ID' => $entityTypeId
		]);
	}

	public static function deleteByFilter(array $filter)
	{
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$tableName = self::getTableName();

		$conditions = array();
		foreach($filter as $k => $v)
		{
			$conditions[] = $helper->prepareAssignment($tableName, $k, $v);
		}
		$connection->queryExecute('DELETE FROM '.$tableName.' WHERE '.implode(' AND ', $conditions));
		static::cleanCache();
	}

	/**
	 * Unbind observers from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if(!is_int($oldEntityTypeID))
		{
			$oldEntityTypeID = (int)$oldEntityTypeID;
		}

		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if(!is_int($oldEntityID))
		{
			$oldEntityID = (int)$oldEntityID;
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if(!is_int($newEntityTypeID))
		{
			$newEntityTypeID = (int)$newEntityTypeID;
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if(!is_int($newEntityID))
		{
			$newEntityID = (int)$newEntityID;
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		$data = array();
		$now = new Main\Type\DateTime();
		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
			"SELECT USER_ID, SORT FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);

		while($fields = $dbResult->fetch())
		{
			$data[] = $fields;
		}

		if(empty($data))
		{
			return;
		}

		foreach($data as $item)
		{
			$queries = $connection->getSqlHelper()->prepareMerge(
				'b_crm_observer',
				array('ENTITY_TYPE_ID', 'ENTITY_ID', 'USER_ID'),
				array(
					'ENTITY_TYPE_ID' => $newEntityTypeID,
					'ENTITY_ID' => $newEntityID,
					'USER_ID' => $item['USER_ID'],
					'SORT' => $item['SORT'],
					'CREATED_TIME' => $now,
					'LAST_UPDATED_TIME' => $now
				),
				array('SORT' => $item['SORT'], 'LAST_UPDATED_TIME' => $now)
			);

			foreach($queries as $query)
			{
				$connection->queryExecute($query);
			}
		}

		$connection->queryExecute(
			"DELETE FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);

		static::cleanCache();
	}
}
