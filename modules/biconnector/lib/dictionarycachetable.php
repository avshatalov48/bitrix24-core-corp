<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

Loc::loadMessages(__FILE__);

/**
 * Class DictionaryCacheTable
 *
 * Fields:
 * <ul>
 * <li> DICTIONARY_ID int mandatory
 * <li> UPDATE_DATE datetime mandatory
 * <li> TTL int mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DictionaryCache_Query query()
 * @method static EO_DictionaryCache_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DictionaryCache_Result getById($id)
 * @method static EO_DictionaryCache_Result getList(array $parameters = [])
 * @method static EO_DictionaryCache_Entity getEntity()
 * @method static \Bitrix\BIConnector\EO_DictionaryCache createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\EO_DictionaryCache_Collection createCollection()
 * @method static \Bitrix\BIConnector\EO_DictionaryCache wakeUpObject($row)
 * @method static \Bitrix\BIConnector\EO_DictionaryCache_Collection wakeUpCollection($rows)
 */

class DictionaryCacheTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_dictionary_cache';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'DICTIONARY_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('DICTIONARY_CACHE_ENTITY_DICTIONARY_ID_FIELD')
				]
			),
			new DatetimeField(
				'UPDATE_DATE',
				[
					'required' => true,
					'title' => Loc::getMessage('DICTIONARY_CACHE_ENTITY_UPDATE_DATE_FIELD')
				]
			),
			new IntegerField(
				'TTL',
				[
					'required' => true,
					'title' => Loc::getMessage('DICTIONARY_CACHE_ENTITY_TTL_FIELD')
				]
			),
		];
	}
}
