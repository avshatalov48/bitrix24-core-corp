<?php declare(strict_types=1);

namespace Bitrix\ImBot\Model;

use Bitrix\Main;

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
 * </ul>
 *
 * @package Bitrix\ImBot
 **/

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
		];
	}
}