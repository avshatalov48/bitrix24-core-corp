<?php

namespace Bitrix\Crm\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class UserMailTemplateAccessTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TEMPLATE_ID int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> ENTITY_TYPE int mandatory
 * </ul>
 *
 * @package Bitrix\Crm
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserMailTemplateAccess_Query query()
 * @method static EO_UserMailTemplateAccess_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UserMailTemplateAccess_Result getById($id)
 * @method static EO_UserMailTemplateAccess_Result getList(array $parameters = [])
 * @method static EO_UserMailTemplateAccess_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_UserMailTemplateAccess createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_UserMailTemplateAccess_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_UserMailTemplateAccess wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_UserMailTemplateAccess_Collection wakeUpCollection($rows)
 */

class UserMailTemplateAccessTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_crm_user_mail_template_access';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('USER_MAIL_TEMPLATE_ACCESS_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'TEMPLATE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('USER_MAIL_TEMPLATE_ACCESS_ENTITY_TEMPLATE_ID_FIELD'),
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('USER_MAIL_TEMPLATE_ACCESS_ENTITY_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'ENTITY_TYPE',
				[
					'required' => true,
					'title' => Loc::getMessage('USER_MAIL_TEMPLATE_ACCESS_ENTITY_ENTITY_TYPE_FIELD'),
				]
			),
		];
	}
}