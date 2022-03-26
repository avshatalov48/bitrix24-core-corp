<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2020 Bitrix
 */
namespace Bitrix\Intranet\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class InvitationTable
 *
 * @package Bitrix\Intranet\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Invitation_Query query()
 * @method static EO_Invitation_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Invitation_Result getById($id)
 * @method static EO_Invitation_Result getList(array $parameters = array())
 * @method static EO_Invitation_Entity getEntity()
 * @method static \Bitrix\Intranet\Internals\EO_Invitation createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\Internals\EO_Invitation_Collection createCollection()
 * @method static \Bitrix\Intranet\Internals\EO_Invitation wakeUpObject($row)
 * @method static \Bitrix\Intranet\Internals\EO_Invitation_Collection wakeUpCollection($rows)
 */
class InvitationTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_intranet_invitation';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'ORIGINATOR_ID' => [
				'data_type' => 'integer',
			],
			'INVITATION_TYPE' => [
				'data_type' => 'string',
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'default_value' => function()
				{
					return new Type\DateTime();
				}
			],
			'INITIALIZED' => [
				'data_type' => 'boolean',
				'values' => [ 'N','Y' ],
				'default_value' => 'N'
			],
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'ORIGINATOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.ORIGINATOR_ID' => 'ref.ID')
			),

		];
	}
}
