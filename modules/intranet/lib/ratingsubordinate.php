<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */
namespace Bitrix\Intranet;

use Bitrix\Main\Entity;

/**
 * Class RatingSubordinateTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RatingSubordinate_Query query()
 * @method static EO_RatingSubordinate_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_RatingSubordinate_Result getById($id)
 * @method static EO_RatingSubordinate_Result getList(array $parameters = array())
 * @method static EO_RatingSubordinate_Entity getEntity()
 * @method static \Bitrix\Intranet\EO_RatingSubordinate createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\EO_RatingSubordinate_Collection createCollection()
 * @method static \Bitrix\Intranet\EO_RatingSubordinate wakeUpObject($row)
 * @method static \Bitrix\Intranet\EO_RatingSubordinate_Collection wakeUpCollection($rows)
 */
class RatingSubordinateTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_rating_subordinate';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'RATING_ID' => array(
				'data_type' => 'integer',
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
			),
			'VOTES' => array(
				'data_type' => 'float',
			),
		);
	}
}