<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceid
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\Faceid;

use Bitrix\Main\Entity;

/**
 * Class FaceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Face_Query query()
 * @method static EO_Face_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Face_Result getById($id)
 * @method static EO_Face_Result getList(array $parameters = array())
 * @method static EO_Face_Entity getEntity()
 * @method static \Bitrix\Faceid\EO_Face createObject($setDefaultValues = true)
 * @method static \Bitrix\Faceid\EO_Face_Collection createCollection()
 * @method static \Bitrix\Faceid\EO_Face wakeUpObject($row)
 * @method static \Bitrix\Faceid\EO_Face_Collection wakeUpCollection($rows)
 */
class FaceTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_face';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('FILE_ID'),
			new Entity\IntegerField('CLOUD_FACE_ID')
		);
	}
}