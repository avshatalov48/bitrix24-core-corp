<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class TemplateMemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateMember_Query query()
 * @method static EO_TemplateMember_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TemplateMember_Result getById($id)
 * @method static EO_TemplateMember_Result getList(array $parameters = [])
 * @method static EO_TemplateMember_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection wakeUpCollection($rows)
 */
class TemplateMemberTable extends TaskDataManager
{
	public const MEMBER_TYPE_ORIGINATOR = MemberTable::MEMBER_TYPE_ORIGINATOR;
	public const MEMBER_TYPE_RESPONSIBLE = MemberTable::MEMBER_TYPE_RESPONSIBLE;
	public const MEMBER_TYPE_ACCOMPLICE = MemberTable::MEMBER_TYPE_ACCOMPLICE;
	public const MEMBER_TYPE_AUDITOR = MemberTable::MEMBER_TYPE_AUDITOR;

	public static function getTableName()
	{
		return 'b_tasks_template_member';
	}

	public static function getObjectClass(): string
	{
		return TemplateMemberObject::class;
	}

	public static function getCollectionClass(): string
	{
		return TemplateMemberCollection::class;
	}

	public static function getClass(): string
	{
		return get_called_class();
	}

	public static function getMap()
	{
		return array(
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'TEMPLATE_ID' => [
				'data_type' => 'integer',
			],
			'USER_ID' => [
				'data_type' => 'integer',
			],
			'TYPE' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateType'],
			],

			// references
			'USER' => [
				'data_type' => UserTable::class,
				'reference' => ['=this.USER_ID' => 'ref.ID']
			],
			'TEMPLATE' => [
				'data_type' => TemplateTable::class,
				'reference' => ['=this.TEMPLATE_ID' => 'ref.ID']
			],
		);
	}

	public static function possibleTypes(): array
	{
		return [
			self::MEMBER_TYPE_ORIGINATOR,
			self::MEMBER_TYPE_RESPONSIBLE,
			self::MEMBER_TYPE_ACCOMPLICE,
			self::MEMBER_TYPE_AUDITOR
		];
	}

	public static function validateType()
	{
		return array(
			new Length(null, 1),
		);
	}
}