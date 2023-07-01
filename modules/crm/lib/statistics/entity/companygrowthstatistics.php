<?php
namespace Bitrix\Crm\Statistics\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm;

/**
 * Class CompanyGrowthStatisticsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CompanyGrowthStatistics_Query query()
 * @method static EO_CompanyGrowthStatistics_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CompanyGrowthStatistics_Result getById($id)
 * @method static EO_CompanyGrowthStatistics_Result getList(array $parameters = [])
 * @method static EO_CompanyGrowthStatistics_Entity getEntity()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_CompanyGrowthStatistics createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_CompanyGrowthStatistics_Collection createCollection()
 * @method static \Bitrix\Crm\Statistics\Entity\EO_CompanyGrowthStatistics wakeUpObject($row)
 * @method static \Bitrix\Crm\Statistics\Entity\EO_CompanyGrowthStatistics_Collection wakeUpCollection($rows)
 */
class CompanyGrowthStatisticsTable  extends Entity\DataManager
{
	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_company_growth_stat';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array('data_type' => 'integer', 'required' => true, 'primary' => true),
			'CREATED_DATE' => array('data_type' => 'date', 'required' => true, 'primary' => true),
			
			'RESPONSIBLE_ID' => array('data_type' => 'integer')
		);
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$fields = array(
			'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0
		);

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_company_growth_stat',
			array('OWNER_ID', 'CREATED_DATE'),
			array_merge(
				$fields,
				array(
					'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
					'CREATED_DATE' => isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : null
				)
			),
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	* @return void
	*/
	public static function deleteByOwner($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		Main\Application::getConnection()->queryExecute("DELETE FROM b_crm_company_growth_stat WHERE OWNER_ID = {$ownerID}");
	}
	/**
	* @return void
	*/
	public static function synchronize($ownerID, array $data)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$userID = isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0;
		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_company_growth_stat SET RESPONSIBLE_ID = {$userID} WHERE OWNER_ID = {$ownerID}"
		);
	}
}