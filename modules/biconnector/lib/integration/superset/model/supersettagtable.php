<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * Class SupersetTagTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int
 * <li> TITLE string(256)
 * </ul>
 *
 * @package Bitrix\BIConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetTag_Query query()
 * @method static EO_SupersetTag_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetTag_Result getById($id)
 * @method static EO_SupersetTag_Result getList(array $parameters = [])
 * @method static EO_SupersetTag_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag_Collection wakeUpCollection($rows)
 */

final class SupersetTagTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_tag';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new Fields\IntegerField('USER_ID'))
				->configureRequired()
			,
			(new Fields\StringField('TITLE'))
				->configureSize(256)
				->configureRequired()
			,
		];
	}
}
