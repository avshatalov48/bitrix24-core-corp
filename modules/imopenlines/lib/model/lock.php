<?php
namespace Bitrix\ImOpenLines\Model;

use \Bitrix\Main,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\ORM\Data\DataManager,
	\Bitrix\Main\ORM\Fields\StringField,
	\Bitrix\Main\Entity\Validator\Length,
	\Bitrix\Main\ORM\Fields\DatetimeField;

/**
 * Class LockTable
 * @package Bitrix\ImOpenLines\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Lock_Query query()
 * @method static EO_Lock_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Lock_Result getById($id)
 * @method static EO_Lock_Result getList(array $parameters = array())
 * @method static EO_Lock_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Lock createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Lock_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Lock wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Lock_Collection wakeUpCollection($rows)
 */
class LockTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_lock';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new StringField('ID', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'validateString'],
			]),
			new DatetimeField('DATE_CREATE', [
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new DatetimeField('LOCK_TIME', [
				'required' => true,
			]),
			new StringField('PID', [
				'validation' => [__CLASS__, 'validateString']
			]),
		];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateString()
	{
		return array(
			new Length(null, 255),
		);
	}

	/**
	 * @return DateTime
	 * @throws Main\ObjectException
	 */
	public static function getCurrentDate()
	{
		return new DateTime();
	}
}