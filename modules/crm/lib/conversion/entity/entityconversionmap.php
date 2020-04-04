<?php
namespace Bitrix\Crm\Conversion\Entity;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Crm;

class EntityConversionMapTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_conv_map';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'SRC_TYPE_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'DST_TYPE_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'LAST_UPDATED' => array('data_type' => 'datetime', 'required' => true),
			'DATA' => array('data_type' => 'string')
		);
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$srcTypeID = isset($data['SRC_TYPE_ID']) ? (int)$data['SRC_TYPE_ID'] : 0;
		$dstTypeID = isset($data['DST_TYPE_ID']) ? (int)$data['DST_TYPE_ID'] : 0;

		$dateField = new DatetimeField('D');
		$lastUpdated = $sqlHelper->convertToDb(new DateTime(), $dateField);

		$data = isset($data['DATA']) ? $sqlHelper->forSql($data['DATA']) : '';

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_conv_map(SRC_TYPE_ID, DST_TYPE_ID, LAST_UPDATED, DATA)
					VALUES({$srcTypeID}, {$dstTypeID}, {$lastUpdated}, '{$data}')
					ON DUPLICATE KEY UPDATE LAST_UPDATED = {$lastUpdated}, DATA = '{$data}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_conv_map WHERE SRC_TYPE_ID = {$srcTypeID} AND DST_TYPE_ID = {$dstTypeID}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_conv_map SET LAST_UPDATED = {$lastUpdated}, DATA = '{$data}'
						WHERE SRC_TYPE_ID = {$srcTypeID} AND DST_TYPE_ID = {$dstTypeID}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_conv_map(SRC_TYPE_ID, DST_TYPE_ID, LAST_UPDATED, DATA)
						VALUES({$srcTypeID}, {$dstTypeID}, {$lastUpdated}, '{$data}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_conv_map USING (SELECT {$srcTypeID} SRC_TYPE_ID, {$dstTypeID} DST_TYPE_ID FROM dual)
				source ON
				(
					source.SRC_TYPE_ID = b_crm_conv_map.SRC_TYPE_ID
					AND source.DST_TYPE_ID = b_crm_conv_map.DST_TYPE_ID
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_conv_map.LAST_UPDATED = {$lastUpdated},
					b_crm_conv_map.DATA = '{$data}'
				WHEN NOT MATCHED THEN
					INSERT (SRC_TYPE_ID, DST_TYPE_ID, LAST_UPDATED, DATA)
					VALUES({$srcTypeID}, {$dstTypeID}, {$lastUpdated}, '{$data}')"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}


