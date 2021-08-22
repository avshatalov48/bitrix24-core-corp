<?php

namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = array())
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Role createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Role wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Role_Collection wakeUpCollection($rows)
 */
class RoleTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_role';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'required' => true,
			)),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
		);
	}
	
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}