<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class MemberNodeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MemberNode_Query query()
 * @method static EO_MemberNode_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_MemberNode_Result getById($id)
 * @method static EO_MemberNode_Result getList(array $parameters = [])
 * @method static EO_MemberNode_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\EO_MemberNode createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\EO_MemberNode_Collection createCollection()
 * @method static \Bitrix\Sign\Internal\EO_MemberNode wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\EO_MemberNode_Collection wakeUpCollection($rows)
 */
class MemberNodeTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_member_node';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		return [
			'MEMBER_ID' => (new IntegerField('MEMBER_ID'))
				->configureTitle('Member id')
				->configurePrimary()
				->configureRequired()
			,
			'NODE_SYNC_ID' => (new IntegerField('NODE_SYNC_ID'))
				->configureTitle('Node sync id')
				->configurePrimary()
				->configureRequired()
			,
			'USER_ID' => (new IntegerField('USER_ID'))
				->configureTitle('User id')
				->configureRequired()
			,
			'DOCUMENT_ID' => (new IntegerField('DOCUMENT_ID'))
				->configureTitle('Document id')
				->configureRequired()
			,
			'DATE_CREATE' => (new Entity\DatetimeField('DATE_CREATE'))
				->configureTitle('Date create')
				->configureRequired()
				->configureDefaultValueNow()
			,
		];
	}
}
