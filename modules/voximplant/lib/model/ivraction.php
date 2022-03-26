<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;

/**
 * Class IvrActionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_IvrAction_Query query()
 * @method static EO_IvrAction_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_IvrAction_Result getById($id)
 * @method static EO_IvrAction_Result getList(array $parameters = array())
 * @method static EO_IvrAction_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_IvrAction createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_IvrAction_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_IvrAction wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_IvrAction_Collection wakeUpCollection($rows)
 */
class IvrActionTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_ivr_action';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'ITEM_ID' => new Entity\IntegerField('ITEM_ID'),
			'DIGIT' => new Entity\StringField('DIGIT', array(
				'size' => 1,
			)),
			'ACTION' => new Entity\StringField('ACTION', array(
				'size' => 255
			)),
			'PARAMETERS' => new Entity\TextField('PARAMETERS', array(
				'serialized' => true,
			)),
			'LEAD_FIELDS' => new Entity\TextField('LEAD_FIELDS', array(
				'serialized' => true
			)),
			'ITEM' => new Entity\ReferenceField('ITEM',
				IvrItemTable::getEntity(),
				array('=this.ITEM_ID' => 'ref.ID'),
				array('join_type' => 'inner')
			),
		);
	}

	public static function deleteByItemId($itemId)
	{
		$itemId = (int)$itemId;
		if($itemId <= 0)
			throw new ArgumentException('Item id should be greater than zero', '$itemId');

		$connection = Application::getConnection();
		$entity = self::getEntity();

		$sql = "DELETE FROM ".$entity->getDBTableName()." WHERE ITEM_ID = ".$itemId;
		$connection->queryExecute($sql);

		$result = new Entity\DeleteResult();
		return $result;
	}
}