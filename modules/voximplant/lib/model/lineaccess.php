<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Voximplant\ConfigTable;

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