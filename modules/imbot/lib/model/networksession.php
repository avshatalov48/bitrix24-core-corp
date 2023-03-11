<?php declare(strict_types=1);

namespace Bitrix\ImBot\Model;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;

/**
 * Tablet class NetworkSessionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> BOT_ID int mandatory
 * <li> DIALOG_ID string mandatory
 * <li> SESSION_ID int optional
 * <li> GREETING_SHOWN bool optional
 * <li> MENU_STATE string
 * <li> TELEMETRY_SENT bool
 * </ul>
 *
 * @package Bitrix\ImBot\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NetworkSession_Query query()
 * @method static EO_NetworkSession_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_NetworkSession_Result getById($id)
 * @method static EO_NetworkSession_Result getList(array $parameters = array())
 * @method static EO_NetworkSession_Entity getEntity()
 * @method static \Bitrix\ImBot\Model\EO_NetworkSession createObject($setDefaultValues = true)
 * @method static \Bitrix\ImBot\Model\EO_NetworkSession_Collection createCollection()
 * @method static \Bitrix\ImBot\Model\EO_NetworkSession wakeUpObject($row)
 * @method static \Bitrix\ImBot\Model\EO_NetworkSession_Collection wakeUpCollection($rows)
 */

class NetworkSessionTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_im_bot_network_session';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'BOT_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DIALOG_ID' => [
				'data_type' => 'string',
				'required' => true,
			],
			'SESSION_ID' => [
				'data_type' => 'integer',
			],
			'GREETING_SHOWN' => [
				'data_type' => 'boolean',
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			],
			'MENU_STATE' => [
				'data_type' => 'string',
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'required' => false,
				'default_value' => [__CLASS__, 'getCurrentDate'],
			],
			'DATE_FINISH' => [
				'data_type' => 'datetime',
				'required' => false,
			],
			'DATE_LAST_ACTIVITY' => [
				'data_type' => 'datetime',
				'required' => false,
				'default_value' => [__CLASS__, 'getCurrentDate'],
			],
			'CLOSE_TERM' => [
				'data_type' => 'integer',
				'default_value' => 1440,
			],
			'CLOSED' => [
				'data_type' => 'boolean',
				'expression' => [
					"CASE WHEN %s IS NULL THEN 0 WHEN %s = '0000-00-00' THEN 0 ELSE %s < NOW() END",
					'DATE_FINISH', 'DATE_FINISH', 'DATE_FINISH',
				]
			],
			'TELEMETRY_SENT' => [
				'data_type' => 'boolean',
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			],
		];
	}

	/**
	 * @return DateTime
	 */
	public static function getCurrentDate(): DateTime
	{
		return new DateTime;
	}
}