<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Query\Query;

class MarketingTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_marketing';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'EVENT' => [
				'data_type' => 'string',
				'required' => true,
			],
			'DATE_CREATED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATE_SHEDULED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATE_EXECUTED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'PARAMS' => [
				'data_type' => 'text',
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * @param array $filter
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteList(array $filter)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		\CTimeZone::disable();
		$sql = sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		);
		$res = $connection->query($sql);
		\CTimeZone::enable();

		return $res;
	}
}