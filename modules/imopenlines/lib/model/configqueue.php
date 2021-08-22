<?php
namespace Bitrix\ImOpenLines\Model;

use \Bitrix\Main,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\Validator\Length;

use	\Bitrix\Main\ORM\Query\Join,
	\Bitrix\Main\ORM\Data\DataManager,
	\Bitrix\Main\ORM\Fields\StringField,
	\Bitrix\Main\ORM\Fields\IntegerField,
	\Bitrix\Main\ORM\Fields\Relations\Reference;

Loc::loadMessages(__FILE__);

/**
 * Class ConfigQueueTable
 * @package Bitrix\ImOpenLines\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ConfigQueue_Query query()
 * @method static EO_ConfigQueue_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ConfigQueue_Result getById($id)
 * @method static EO_ConfigQueue_Result getList(array $parameters = array())
 * @method static EO_ConfigQueue_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_ConfigQueue_Collection wakeUpCollection($rows)
 */
class ConfigQueueTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_config_queue';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new IntegerField('SORT', [
				'default_value' => '0',
			]),
			new IntegerField('CONFIG_ID', [
				'required' => true,
			]),
			new IntegerField('ENTITY_ID', [
				'required' => true,
			]),
			new StringField('ENTITY_TYPE', [
				'required' => true,
				'validation' => [__CLASS__, 'validateString'],
			]),
			new Reference(
				'CONFIG',
				ConfigTable::class,
				Join::on('this.CONFIG_ID', 'ref.ID')
			)
		];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateString()
	{
		return [
			new Length(null, 255),
		];
	}
}