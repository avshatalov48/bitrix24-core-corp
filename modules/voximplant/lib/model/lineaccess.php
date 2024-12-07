<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Voximplant\ConfigTable;

/**
 * Class LineAccessTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_LineAccess_Query query()
 * @method static EO_LineAccess_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_LineAccess_Result getById($id)
 * @method static EO_LineAccess_Result getList(array $parameters = [])
 * @method static EO_LineAccess_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_LineAccess createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_LineAccess_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_LineAccess wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_LineAccess_Collection wakeUpCollection($rows)
 */
class LineAccessTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_line_access';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('CONFIG_ID'),
			new Entity\StringField('ACCESS_CODE'),
			new Entity\ReferenceField('CONFIG', ConfigTable::getEntity(),
				array('=this.CONFIG_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
		);
	}

	public static function deleteByConfigId($configId)
	{
		$connection =  Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		return $connection->query("DELETE FROM ".static::getTableName()." WHERE CONFIG_ID='".$sqlHelper->forSql($configId)."'");
	}
}