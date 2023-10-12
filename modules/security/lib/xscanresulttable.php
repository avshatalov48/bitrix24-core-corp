<?php

namespace Bitrix\Security;

use Bitrix\Main\ORM\Query\Query;

/**
 * Class XScanResultTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_XScanResult_Query query()
 * @method static EO_XScanResult_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_XScanResult_Result getById($id)
 * @method static EO_XScanResult_Result getList(array $parameters = [])
 * @method static EO_XScanResult_Entity getEntity()
 * @method static \Bitrix\Security\XScanResult createObject($setDefaultValues = true)
 * @method static \Bitrix\Security\XScanResults createCollection()
 * @method static \Bitrix\Security\XScanResult wakeUpObject($row)
 * @method static \Bitrix\Security\XScanResults wakeUpCollection($rows)
 */
class XScanResultTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_xscan_results';
	}

	public static function getMap()
	{
		return array(
			new \Bitrix\Main\Entity\IntegerField('id', array('primary' => true, 'autocomplete' => true)),
			new \Bitrix\Main\Entity\EnumField('type', array(
				'values' => array('file', 'agent', 'event'),
				'default_value' => 'file'
			)),
			new \Bitrix\Main\Entity\StringField('src'),
			new \Bitrix\Main\Entity\StringField('message'),
			new \Bitrix\Main\Entity\FloatField('score'),
			new \Bitrix\Main\Entity\DatetimeField('ctime'),
			new \Bitrix\Main\Entity\DatetimeField('mtime'),
			new \Bitrix\Main\Entity\StringField('tags')
		);
	}

	public static function getCollectionClass()
	{
		return XScanResults::class;
	}

	public static function getObjectClass()
	{
		return XScanResult::class;
	}

	public static function deleteList(array $filter)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		$where = Query::buildFilterSql($entity, $filter);
		$where = $where ? 'WHERE ' . $where : '';

		$sql = sprintf(
			'DELETE FROM %s %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			$where
		);

		$res = $connection->query($sql);

		return $res;
	}

}

class XScanResults extends EO_XScanResult_Collection
{
}

class XScanResult extends EO_XScanResult
{
}
