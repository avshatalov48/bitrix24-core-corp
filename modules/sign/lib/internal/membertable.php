<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Sign\File;

/**
 * Class MemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Member_Query query()
 * @method static EO_Member_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Member_Result getById($id)
 * @method static EO_Member_Result getList(array $parameters = [])
 * @method static EO_Member_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Member createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\MemberCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Member wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\MemberCollection wakeUpCollection($rows)
 */
class MemberTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return Member::class;
	}

	public static function getCollectionClass(): string
	{
		return MemberCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_member';
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
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'DOCUMENT_ID' => new Entity\IntegerField('DOCUMENT_ID', [
				'title' => 'Document id',
				'required' => true
			]),
			'DOCUMENT' => new Entity\ReferenceField(
				'DOCUMENT', DocumentTable::class, ['=this.DOCUMENT_ID' => 'ref.ID']
			),
			'CONTACT_ID' => new Entity\IntegerField('CONTACT_ID', [
				'title' => 'Contact id',
				'required' => true
			]),
            'CONTACT' => new Entity\ReferenceField(
                'CONTACT', \Bitrix\Crm\ContactTable::class, ['=this.CONTACT_ID' => 'ref.ID']
            ),
			'PART' => new Entity\IntegerField('PART', [
				'title' => 'Sign part number',
				'required' => true
			]),
			'HASH' => new Entity\StringField('HASH', [
				'title' => 'Hash',
				'required' => true
			]),
			'SIGNED' => new Entity\StringField('SIGNED', [
				'title' => 'Member signed',
				'required' => true,
				'default_value' => 'N'
			]),
			'VERIFIED' => new Entity\StringField('VERIFIED', [
				'title' => 'Member verified',
				'required' => true,
				'default_value' => 'N'
			]),
			'MUTE' => new Entity\StringField('MUTE', [
				'title' => 'Not send link to this member',
				'required' => true,
				'default_value' => 'N'
			]),
			'COMMUNICATION_TYPE' => new Entity\StringField('COMMUNICATION_TYPE', [
				'title' => 'Member communication type'
			]),
			'COMMUNICATION_VALUE' => new Entity\StringField('COMMUNICATION_VALUE', [
				'title' => 'Member communication value'
			]),
			'USER_DATA' => (new \Bitrix\Main\ORM\Fields\ArrayField('USER_DATA', [
				'title' => 'User data after signing document'
			]))->configureSerializationJson(),
			'META' => (new \Bitrix\Main\ORM\Fields\ArrayField('META', [
				'title' => 'Meta information'
			]))->configureSerializationJson(),
			'SIGNATURE_FILE_ID' => (new Entity\IntegerField('SIGNATURE_FILE_ID', [
				'title' => 'Signature file id'
			]))
				->configureNullable()
			,
			'STAMP_FILE_ID' => (new Entity\IntegerField('STAMP_FILE_ID', [
				'title' => 'Stamp file id'
			]))
				->configureNullable()
			,
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', [
				'title' => 'Created on',
				'required' => true
			]),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', [
				'title' => 'Modified on',
				'required' => true
			]),
			'DATE_SIGN' => new Entity\DatetimeField('DATE_SIGN', [
				'title' => 'Signed on',
			]),
			'DATE_DOC_DOWNLOAD' => new Entity\DatetimeField('DATE_DOC_DOWNLOAD', [
				'title' => 'Downloaded on',
			]),
			'DATE_DOC_VERIFY' => new Entity\DatetimeField('DATE_DOC_VERIFY', [
				'title' => 'Verified on',
			]),
			'IP' => new StringField('IP'),
			'TIME_ZONE_OFFSET' => new IntegerField('TIME_ZONE_OFFSET'),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', [
				'title' => 'Entity id',
			]),
			'ENTITY_TYPE' => new Entity\StringField('ENTITY_TYPE', [
				'title' => 'Entity type',
			]),
			'PRESET_ID' => new Entity\IntegerField('PRESET_ID', [
				'title' => 'Preset id',
			]),
			'UID' => new Entity\StringField('HASH', [
				'title' => 'Uid',
			]),
			'ROLE' => (new IntegerField('ROLE'))
				->configureTitle('Role')
				->configureNullable()
			,
			'REMINDER_TYPE' => (new IntegerField('REMINDER_TYPE'))
				->configureTitle('Reminder type')
				->configureNullable()
			,
			'REMINDER_LAST_SEND_DATE' => (new Entity\DatetimeField('REMINDER_LAST_SEND_DATE'))
				->configureTitle('Reminder last send date')
				->configureNullable()
			,
			'REMINDER_PLANNED_NEXT_SEND_DATE' => (new Entity\DatetimeField('REMINDER_PLANNED_NEXT_SEND_DATE'))
				->configureTitle('Reminder planned next send date')
				->configureNullable()
			,
			'REMINDER_COMPLETED' => (new Entity\BooleanField('REMINDER_COMPLETED'))
				->configureTitle('Reminder completed')
				->configureValues(0, 1)
				->configureDefaultValue(false)
				->configureNullable(false)
			,
			'REMINDER_START_DATE' => (new Entity\DatetimeField('REMINDER_START_DATE'))
				->configureTitle('Reminder start date')
				->configureNullable()
			,
			'CONFIGURED' => (new IntegerField('CONFIGURED'))
				->configureTitle('Configured')
				->configureNullable()
			,
			'EMPLOYEE_ID' => (new IntegerField('EMPLOYEE_ID'))
				->configureTitle('Employee ID')
				->configureNullable()
			,
			'HCMLINK_JOB_ID' => (new IntegerField('HCMLINK_JOB_ID'))
				->configureTitle('HcmLink Job Id')
				->configureNullable()
			,
			'DATE_SEND' => (new Entity\DatetimeField('DATE_SEND'))
				->configureTitle('Send on')
				->configureNullable()
			,
			'DATE_STATUS_CHANGED' => (new Entity\DatetimeField('DATE_STATUS_CHANGED'))
				->configureTitle('Status change date')
				->configureNullable()
			,
		];
	}

	/**
	 * Before delete handler.
	 *
	 * @param Entity\Event $event Event instance.
	 *
	 * @return Entity\EventResult
	 */
	public static function onBeforeDelete(Entity\Event $event): Entity\EventResult
	{
		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		// delete member's files
		if ($primary['ID'] ?? null)
		{
			$res = self::getList([
				'select' => [
					'SIGNATURE_FILE_ID',
					'STAMP_FILE_ID'
				],
				'filter' => [
					'ID' => $primary['ID']
				],
				'limit' => 1
			]);
			if ($row = $res->fetch())
			{
				if ($row['SIGNATURE_FILE_ID'])
				{
					File::delete($row['SIGNATURE_FILE_ID']);
				}
				if ($row['STAMP_FILE_ID'])
				{
					File::delete($row['STAMP_FILE_ID']);
				}
			}
		}

		return $result;
	}
}
