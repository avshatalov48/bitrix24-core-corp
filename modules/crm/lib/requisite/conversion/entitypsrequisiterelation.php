<?php
namespace Bitrix\Crm\Requisite\Conversion;
use Bitrix\Main;

class EntityPSRequisiteRelation
{
	/**
	 * @param $entityId
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function getByEntity($entityId)
	{
		$dbResult = PSRequisiteRelationTable::getList(
			array(
				'filter' => array('=ENTITY_ID' => $entityId),
				'select' => array('COMPANY_ID', 'REQUISITE_ID', 'BANK_DETAIL_ID'),
				'limit' => 1
			)
		);
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}

	/**
	 * @param $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($parameters)
	{
		return PSRequisiteRelationTable::getList($parameters);
	}

	/**
	 * @param $entityId
	 * @param $companyId
	 * @param $requisiteId
	 * @param int $bankDetailId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function register($entityId, $companyId = 0, $requisiteId = 0, $bankDetailId = 0)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'psId');

		$companyId = (int)$companyId;
		if($companyId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'companyId');

		$requisiteId = (int)$requisiteId;
		if($requisiteId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$bankDetailId = (int)$bankDetailId;
		if($bankDetailId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'bankDetailId');

		PSRequisiteRelationTable::upsert(
			array(
				'ENTITY_ID' => $entityId,
				'COMPANY_ID' => $companyId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId
			)
		);
	}

	/**
	 * @param $entityId
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function unregister($entityId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'psId');

		PSRequisiteRelationTable::delete(array('ENTITY_ID' => $entityId));
	}

	/**
	 * @param $requisiteId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function unregisterByRequisite($requisiteId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$requisiteId = (int)$requisiteId;
		if ($requisiteId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$connection = Main\Application::getConnection();

		if($connection instanceof Main\DB\MysqlCommonConnection
			|| $connection instanceof Main\DB\MssqlConnection
			|| $connection instanceof Main\DB\OracleConnection)
		{
			$tableName = PSRequisiteRelationTable::getTableName();
			if ($connection instanceof Main\DB\MssqlConnection
				|| $connection instanceof Main\DB\OracleConnection)
			{
				$tableName = strtoupper($tableName);
			}
			$connection->queryExecute(
				"DELETE FROM {$tableName} WHERE REQUISITE_ID = {$requisiteId}"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}

	public static function unregisterAll()
	{
		$connection = Main\Application::getConnection();

		if($connection instanceof Main\DB\MysqlCommonConnection
			|| $connection instanceof Main\DB\MssqlConnection
			|| $connection instanceof Main\DB\OracleConnection)
		{
			$tableName = PSRequisiteRelationTable::getTableName();
			if ($connection instanceof Main\DB\MssqlConnection
				|| $connection instanceof Main\DB\OracleConnection)
			{
				$tableName = strtoupper($tableName);
			}
			$connection->queryExecute(
				"DELETE FROM {$tableName}"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}