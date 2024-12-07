<?php
namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetUser_Query query()
 * @method static EO_SupersetUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetUser_Result getById($id)
 * @method static EO_SupersetUser_Result getList(array $parameters = [])
 * @method static EO_SupersetUser_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetUser_Collection wakeUpCollection($rows)
 */

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
			new StringField(
				'PERMISSION_HASH',
				[
					'required' => false,
					'validation' => fn() => [new LengthValidator(null, 32)],
					'title' => Loc::getMessage('SUPERSET_USER_ENTITY_PERMISSION_HASH_FIELD'),
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

	/**
	 * @param int $userId
	 * @param string $permissionHash
	 * @return UpdateResult
	 * @throws ObjectNotFoundException
	 */
	public static function updatePermissionHash(int $userId, string $permissionHash): UpdateResult
	{
		$result = self::getRow([
			'select' => ['ID'],
			'filter' => ['=USER_ID' => $userId],
		]);

		if (!$result)
		{
			throw new ObjectNotFoundException("User with USER_ID: {$userId} not found");
		}

		return self::update($result['ID'], ['PERMISSION_HASH' => $permissionHash]);
	}
}
