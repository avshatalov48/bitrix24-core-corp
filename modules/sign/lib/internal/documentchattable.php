<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class DocumentChatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CHAT_ID int mandatory
 * <li> DOCUMENT_ID int mandatory
 * <li> TYPE int mandatory
 * </ul>
 *
 * @package Bitrix\Sign\internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DocumentChat_Query query()
 * @method static EO_DocumentChat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DocumentChat_Result getById($id)
 * @method static EO_DocumentChat_Result getList(array $parameters = [])
 * @method static EO_DocumentChat_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\DocumentChat createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\EO_DocumentChat_Collection createCollection()
 * @method static \Bitrix\Sign\Internal\DocumentChat wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\EO_DocumentChat_Collection wakeUpCollection($rows)
 */

class DocumentChatTable extends DataManager
{
	public static function getObjectClass(): string
	{
		return DocumentChat::class;
	}
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sign_document_chat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureSize(8)
			,
			(new IntegerField('CHAT_ID'))
				->configureRequired()
				->configureSize(8)
			,
			(new IntegerField('DOCUMENT_ID'))
				->configureRequired()
				->configureSize(8)
			,
			(new IntegerField('TYPE'))
				->configureRequired()
				->configureSize(1)
			,
		];
	}
}