<?php
namespace Bitrix\Sign\Internal\SignersList;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;

/**
 * Class SignersListUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SignersListUser_Query query()
 * @method static EO_SignersListUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SignersListUser_Result getById($id)
 * @method static EO_SignersListUser_Result getList(array $parameters = [])
 * @method static EO_SignersListUser_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListUserCollection createCollection()
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListUser wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListUserCollection wakeUpCollection($rows)
 */
class SignersListUserTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return SignersListUser::class;
	}

	public static function getCollectionClass(): string
	{
		return SignersListUserCollection::class;
	}

	public static function getTableName()
	{
		return 'b_sign_signers_list_user';
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('LIST_ID'))
				->configurePrimary()
			,
			(new Fields\IntegerField('USER_ID'))
				->configurePrimary()
			,
			(new Fields\IntegerField('CREATED_BY_ID'))
				->configureRequired()
				->configureNullable(false)
			,
			(new Fields\DatetimeField('DATE_CREATE'))
				->configureRequired()
				->configureNullable(false)
			,
			new Fields\Relations\Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID'),
			),
			new \Bitrix\Main\Entity\ExpressionField(
				'USER_SEARCH_NAME',
				'CONCAT_WS(" ", %s, %s, %s)',
				['USER.LAST_NAME', 'USER.NAME', 'USER.SECOND_NAME']
			),
		];
	}
}
