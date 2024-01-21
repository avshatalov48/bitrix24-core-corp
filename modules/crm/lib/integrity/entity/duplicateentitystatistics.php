<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateEntityStatisticsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateEntityStatistics_Query query()
 * @method static EO_DuplicateEntityStatistics_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateEntityStatistics_Result getById($id)
 * @method static EO_DuplicateEntityStatistics_Result getList(array $parameters = [])
 * @method static EO_DuplicateEntityStatistics_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateEntityStatistics createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateEntityStatistics_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateEntityStatistics wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_DuplicateEntityStatistics_Collection wakeUpCollection($rows)
 */
class DuplicateEntityStatisticsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_entity_stat';
	}

	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'RANKING_DATA' => array(
				'data_type' => 'string'
			)
		);
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityID = isset($data['ENTITY_ID']) ? intval($data['ENTITY_ID']) : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? intval($data['ENTITY_TYPE_ID']) : 0;
		$rankingData = isset($data['RANKING_DATA']) ? $data['RANKING_DATA'] : '';
		$rankingData = mb_substr($rankingData, 0, 512);

		$sql = $sqlHelper->prepareMerge(
			'b_crm_dp_entity_stat',
			[
				'ENTITY_ID',
				'ENTITY_TYPE_ID',
			],
			[
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'RANKING_DATA' => $rankingData,
			],
			[
				'RANKING_DATA' => $rankingData,
			]
		);
		$connection->queryExecute($sql[0]);
	}
}