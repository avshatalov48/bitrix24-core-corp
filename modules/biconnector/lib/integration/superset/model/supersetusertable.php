<?php
namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class SupersetUserTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> CLIENT_ID string(32) mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector\Integration\Superset\Model
 **/

class SupersetUserTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_superset_user';
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
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('SUPERSET_USER_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SUPERSET_USER_ENTITY_USER_ID_FIELD'),
				]
			),
			new StringField(
				'CLIENT_ID',
				[
					'required' => true,
					'validation' => fn() => [new LengthValidator(null, 32)],
					'title' => Loc::getMessage('SUPERSET_USER_ENTITY_CLIENT_ID_FIELD'),
				]
			),
		];
	}

	public static function addClientId(int $userId, string $clientId): AddResult
	{
		return self::add([
			'USER_ID' => $userId,
			'CLIENT_ID' => $clientId,
		]);
	}
}
